# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

**recapdam** is an asset-management (*patrimonio*) platform where users upload evidence for CAPDAM custody records (*resguardos*). The README, UI and domain language are in Spanish; code identifiers for domain concepts are Spanish too (`Persona`, `Equipo`, `Resguardo`, `Evidencia`).

Core domain:

- A **resguardo** records that a device (PC, equipment, any item) is assigned to a person, who can be an employee or a client.
- **Evidencias** are uploaded for each assignment. Each one is a photo or scan (for example the serial number label or a signed resguardo) plus typed data: the device's serial number and the name of the person it is assigned to.
- Only CAPDAM staff sign in and use the app. The employees and clients who receive devices do not have accounts.

## Stack

Laravel 13 (PHP 8.5) with MariaDB, and Filament 5 for the entire staff UI at `/admin`. Build screens with Filament resources, schemas, tables and actions rather than custom Blade pages or routes. There is no front-end build step: Filament ships prebuilt assets, so Node is only needed for a custom Filament theme.

## Commands

```sh
docker compose up -d --wait       # MariaDB on 127.0.0.1:3306, credentials read from .env
php artisan migrate
php artisan serve                 # http://localhost:8000/admin
php artisan make:filament-user    # create a staff login (interactive)

php artisan test                                   # full suite
php artisan test --filter=test_staff_can_create_a_resguardo   # single test
```

Tests run against the `recapdam_test` database (set in `phpunit.xml`), which `docker/mariadb/01-test-database.sh` creates only when the MariaDB volume is first initialized. If it's missing, recreate the volume with `docker compose down -v && docker compose up -d --wait`, which wipes local data. SQLite is not used.

## Architecture

- **Data model** (`app/Models`): `Resguardo` belongs to a `Persona` and an `Equipo` and has many `Evidencia`. A resguardo is active while `fecha_devolucion` is null; `Equipo::resguardoActivo()` and the `Resguardo::activos()` scope build on that.
- **Business rule: one open resguardo per device.** This is enforced in form validation (`ResguardoForm::equipoDisponible`), not by a DB constraint. Returning a device goes through `RegistrarDevolucionAction`, which sets `fecha_devolucion`.
- **History is protected.** Foreign keys from `resguardos` to `personas` and `equipos` are `restrictOnDelete`, and the Filament delete actions are hidden for records that have resguardos.
- **Evidencia files** are stored on the private `local` disk (`storage/app/private/evidencias`) and served through temporary signed URLs. `numero_serie` and `nombre_persona` on an evidencia are copied from the resguardo when the form opens, then saved independently. The "Coincide" column flags evidencias whose typed values differ from the resguardo.
- **File cleanup lives in model events.** `Evidencia` deletes its file on delete or replace. `Resguardo` deletes its evidencias through Eloquent before it is deleted, because the DB cascade would skip those events and leave files behind. Mass deletes such as `Evidencia::query()->delete()` bypass this.
- **Vehicles vs. other bienes.** `Equipo::categoria` set to `Equipo::CATEGORIA_VEHICULO` turns on the vehicle fields. For vehicles, `modelo` holds the *línea* (e.g. ESTACA NP300) and `anio` the model year, matching the paper form's labels. `clase` and `subtipo` ("Tipo" in the UI) are the patrimonio catalog values, separate from `categoria`.
- **Signatures are on paper and tracked in the app.** `Resguardo::sincronizarFirmas()` runs on every save and keeps one `Firma` row per required `RolFirma`: 4 for vehicles, 2 (Entrega, Recibe) otherwise. It only touches unsigned rows, and signed rows keep the name they were signed with. Names come from `Firmante` for the fixed roles (`RolFirma::fijos()`), from `director_area_*` on the resguardo, and from the persona for Recibe. Editing a `Firmante` updates pending signatures.
- **Printable format.** `App\Support\ResguardoPdf` renders `resources/views/pdf/resguardo.blade.php` with dompdf (used directly, since there's no Laravel 13 wrapper; it needs PHP's `iconv`). The template copies the official CAPDAM "Resguardo de Vehículos" layout and switches to "Resguardo de Bienes" for other categories. The logo is read from `public/img/logo-capdam.png` when present.
- `registrado_por` and `subido_por` are set automatically from the logged-in user in `creating` model events.
- Filament resources live in `app/Filament/Resources/<Plural>/` with separate `Schemas/` and `Tables/` classes. Evidencias are managed only through `EvidenciasRelationManager` on the resguardo view page. Personas and equipos show a read-only resguardo history.
- **Roles** (`App\Enums\RolUsuario` in `users.rol`):
  - **Root Admin** can do everything.
  - **Patrimonio** creates and edits personas, equipos, resguardos and evidencias, marks signatures and downloads PDFs. It can't delete records, unmark signatures, or open Configuración (Usuarios, Firmantes).
  - Permissions live in `app/Policies` (auto-discovered, and Filament hides actions accordingly). The one exception is `FirmasRelationManager`'s "Desmarcar" action, which checks `esAdmin()` inline.
- **User accounts.** `canAccessPanel()` returns `users.activo`, and Filament returns 403 without it outside local. Accounts are deactivated, never deleted, so audit columns keep pointing at real users. Admins can't change their own role or active flag, which guarantees at least one admin remains. `php artisan make:filament-user` creates a **Patrimonio** user (the column default); create admins from the Usuarios screen, or set `rol = 'admin'`.
