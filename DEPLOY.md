# Deploying recapdam with Docker

recapdam runs as two containers: the app (PHP 8.5 + Apache) and its own MariaDB. **Nothing is installed
on the host except Docker**, and nothing touches the host's PHP, MySQL or other sites:

- The app container listens only on `127.0.0.1:8090` (configurable with `APP_PORT`).
- The database container publishes **no port**. It can't clash with a MySQL/MariaDB already on the host.
- The host's Apache gets one extra site that forwards the recapdam domain to `127.0.0.1:8090` and
  handles HTTPS. Other sites are not affected.

Throughout this guide:

```bash
DOMINIO=recapdam.capdam.gob.mx     # the name in your DNS A record
cd /var/www/recapdam               # where the repository is cloned
```

`dc` below stands for `docker compose -f docker-compose.prod.yml`:

```bash
alias dc='docker compose -f docker-compose.prod.yml'
```

---

## 0. Read-only checks (change nothing)

Run these first and stop if something doesn't match.

```bash
docker --version && docker compose version     # Docker and the compose plugin installed?
sudo ss -ltnp | grep -E ':80 |:443 |:8090 '      # 80/443 must be apache2; 8090 must be FREE
sudo apache2ctl -S 2>&1 | head -30               # existing sites (note the default one per port)
sudo apache2ctl -M 2>/dev/null | grep -E 'proxy_module|proxy_http|headers|ssl'
dig +short "$DOMINIO"                            # must print the server's IP
df -h /var/lib/docker /var/www
```

- If **8090 is taken**, pick another free port and set `APP_PORT` in `.env` (step 2) and in the Apache
  site (step 4).
- If **Docker is missing**, install it. **Always simulate first** and make sure apt removes nothing:

  ```bash
  sudo apt-get install -s docker.io docker-compose-v2 | grep -E '^(Remv|Inst)'
  # Only "Inst" lines? Then install for real:
  sudo apt-get install docker.io docker-compose-v2
  sudo usermod -aG docker "$USER"    # log out and back in to use docker without sudo
  ```

  **Never confirm an `apt` command whose simulation shows `Remv` lines** (that's how MySQL got
  replaced on 2026-11-23).

---

## 1. Code

```bash
cd /var/www/recapdam
git status                 # should be clean; vendor/ and other ignored files don't matter
git pull
```

(Fresh server: `sudo git clone https://github.com/CapdamDev/recapdam.git /var/www/recapdam` and
`sudo chown -R "$USER": /var/www/recapdam`.)

---

## 2. Production `.env`

```bash
[ -f .env ] && mv .env ".env.respaldo-$(date +%F)"   # keep any earlier attempt
cp .env.production.example .env
chmod 600 .env
dc build
```

Fill in `.env`:

| Variable | Value |
|---|---|
| `APP_KEY` | output of `dc run --rm --no-deps --entrypoint php app artisan key:generate --show` |
| `APP_URL` | `https://$DOMINIO` (exact public address, no trailing slash) |
| `DB_PASSWORD` | output of `openssl rand -hex 24` |
| `APP_PORT` | `8090`, or the free port from step 0 |

`APP_KEY` encrypts sessions. Keep it with your backups, and don't change it once the app is in use.

---

## 3. Start the containers

```bash
dc up -d --wait
dc ps                                       # app and db "healthy"
dc logs app | tail -30                      # migrations ran, no errors
curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8090/admin/login   # 200
```

At this point the app runs but isn't reachable from outside yet.

---

## 4. Apache site (reverse proxy)

Enabling these modules only loads them; sites that don't use them behave exactly as before.

```bash
sudo a2enmod proxy proxy_http headers
```

Create `/etc/apache2/sites-available/zz-recapdam.conf`. The `zz-` prefix keeps it from becoming the
default site for requests that match no `ServerName`. Replace the domain, and the port if you changed it:

```apache
<VirtualHost *:80>
    ServerName recapdam.capdam.gob.mx

    ProxyPreserveHost On
    ProxyTimeout 120
    # Tells Laravel whether the visitor used http or https (needed for links and uploads).
    RequestHeader set X-Forwarded-Proto expr=%{REQUEST_SCHEME}
    ProxyPass        / http://127.0.0.1:8090/
    ProxyPassReverse / http://127.0.0.1:8090/

    ErrorLog ${APACHE_LOG_DIR}/recapdam-error.log
    CustomLog ${APACHE_LOG_DIR}/recapdam-access.log combined
</VirtualHost>
```

Enable it, then **check the other sites still answer right away**:

```bash
sudo a2ensite zz-recapdam
sudo apache2ctl configtest          # must say "Syntax OK"; if not, fix before reloading
sudo systemctl reload apache2
curl -sI https://turnos.capdam.gob.mx | head -1     # same answer as before (200/302)
curl -sI "http://$DOMINIO" | head -1                # 302 = recapdam answering
```

**Undo** (if anything else misbehaves): `sudo a2dissite zz-recapdam && sudo systemctl reload apache2`.

> If you created `/etc/apache2/sites-available/recapdam.conf` from the earlier (non-Docker) guide,
> make sure it's disabled (`sudo a2dissite recapdam`) and delete it. Also check `php8.3-fpm` is still
> disabled: `ls /etc/apache2/conf-enabled/ | grep fpm` should print nothing.

### HTTPS

```bash
sudo certbot --apache -d "$DOMINIO"
curl -sI "https://$DOMINIO" | head -1                # 302
curl -sI https://turnos.capdam.gob.mx | head -1      # still fine
```

Certbot copies the site into `zz-recapdam-le-ssl.conf`. The `X-Forwarded-Proto` line works for both
http and https, so nothing needs editing.

---

## 5. First admin and initial data

```bash
dc exec app php artisan make:filament-user           # asks name, email, password
dc exec app php artisan usuarios:hacer-admin sistemas@capdam.gob.mx
```

Then sign in at `https://$DOMINIO` and:

1. **Catálogo de partidas → Importar catálogo**: upload `partidas_sistema_recapdam.csv`.
2. **Configuración → Firmantes**: Director Administrativo and Jefe de Bienes Patrimoniales.
3. **Configuración → Ajustes**: logo, favicon and colors.
4. **Configuración → Usuarios**: accounts for the Patrimonio staff.

---

## Updating

```bash
cd /var/www/recapdam
git pull
dc up -d --build --wait      # rebuilds the image; migrations run automatically on start
dc logs app | tail -20
```

Data and uploaded files live in Docker volumes and survive rebuilds. **Never run `dc down -v`**:
`-v` deletes the volumes, which means the database and every uploaded file.

---

## Backups

Two things hold data: the database and the `storage` volume (evidencias, logo, favicon).

```bash
mkdir -p ~/respaldos-recapdam && cd ~/respaldos-recapdam
F=$(date +%F)

# Database
docker compose -f /var/www/recapdam/docker-compose.prod.yml exec -T db \
  sh -c 'mariadb-dump -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' > "recapdam-db-$F.sql"

# Uploaded files
docker run --rm -v recapdam-prod_storage:/data:ro -v "$PWD":/respaldo alpine:3.20 \
  tar czf "/respaldo/recapdam-storage-$F.tgz" -C /data .

# Keep a copy of /var/www/recapdam/.env (APP_KEY and DB password) somewhere safe too.
```

Restore the database: `dc exec -T db sh -c 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE"' < recapdam-db-FECHA.sql`.

---

## Useful commands

```bash
dc ps                          # status
dc logs -f app                 # live logs (Laravel errors show up here)
dc exec app php artisan about  # environment summary
dc restart app                 # restart only the app
dc down                        # stop (keeps data); start again with: dc up -d
```
