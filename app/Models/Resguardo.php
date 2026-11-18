<?php

namespace App\Models;

use App\Enums\RolFirma;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resguardo extends Model
{
    /** @use HasFactory<\Database\Factories\ResguardoFactory> */
    use HasFactory;

    protected $fillable = [
        'persona_id',
        'equipo_id',
        'adscripcion',
        'director_area_cargo',
        'director_area_nombre',
        'fecha_asignacion',
        'fecha_devolucion',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_asignacion' => 'date',
            'fecha_devolucion' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Resguardo $resguardo) {
            $resguardo->registrado_por ??= auth()->id();
        });

        // Keep the required signature rows in step with the device type and the people involved.
        static::saved(fn (Resguardo $resguardo) => $resguardo->sincronizarFirmas());

        // Delete evidencias through Eloquent (not the DB cascade) so their files are removed too.
        static::deleting(function (Resguardo $resguardo) {
            $resguardo->evidencias()->each(fn (Evidencia $evidencia) => $evidencia->delete());
        });
    }

    /** @return BelongsTo<Persona, $this> */
    public function persona(): BelongsTo
    {
        return $this->belongsTo(Persona::class);
    }

    /** @return BelongsTo<Equipo, $this> */
    public function equipo(): BelongsTo
    {
        return $this->belongsTo(Equipo::class);
    }

    /** @return BelongsTo<User, $this> */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    /** @return HasMany<Evidencia, $this> */
    public function evidencias(): HasMany
    {
        return $this->hasMany(Evidencia::class);
    }

    /** @return HasMany<Firma, $this> */
    public function firmas(): HasMany
    {
        return $this->hasMany(Firma::class);
    }

    public function firmasCompletas(): bool
    {
        return $this->firmas->every(fn (Firma $firma) => $firma->estaFirmada());
    }

    /**
     * Creates the signature rows this resguardo needs (4 for vehicles, 2 otherwise) and refreshes
     * the name and position of unsigned ones. Signed rows are never changed or removed.
     */
    public function sincronizarFirmas(): void
    {
        // The persona or equipo may have just changed, so don't trust previously loaded relations.
        $this->unsetRelation('equipo')->unsetRelation('persona');

        $requeridas = RolFirma::requeridas($this->equipo->esVehiculo());

        $this->firmas()
            ->whereNull('firmado_en')
            ->whereNotIn('rol', $requeridas)
            ->delete();

        foreach ($requeridas as $rol) {
            $firma = $this->firmas()->firstOrNew(['rol' => $rol]);

            if ($firma->estaFirmada()) {
                continue;
            }

            [$nombre, $cargo] = match ($rol) {
                RolFirma::VoBoDirectorArea => [$this->director_area_nombre, $this->director_area_cargo],
                RolFirma::Recibe => [$this->persona->nombre, null],
                default => [Firmante::nombrePara($rol), $rol->cargo()],
            };

            $firma->fill(['nombre' => $nombre, 'cargo' => $cargo])->save();
        }

        $this->unsetRelation('firmas');
    }

    public function estaActivo(): bool
    {
        return $this->fecha_devolucion === null;
    }

    /** @param Builder<Resguardo> $query */
    public function scopeActivos(Builder $query): void
    {
        $query->whereNull('fecha_devolucion');
    }
}
