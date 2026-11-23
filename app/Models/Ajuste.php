<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * System branding. There is a single row; read it with Ajuste::actual().
 */
class Ajuste extends Model
{
    public const NOMBRE_DEFAULT = 'recapdam';

    /** CAPDAM's institutional palette, restored by "Restablecer paleta institucional". */
    public const PALETA_INSTITUCIONAL = [
        'color_principal' => '#7f2841',
        'color_acento' => '#b69664',
        'color_principal_claro' => '#923244',
        'color_principal_oscuro' => '#612233',
        'color_texto' => '#161a1d',
    ];

    // Holds a plain attributes array: the cache refuses to unserialize objects (cache.serializable_classes).
    private const CACHE_KEY = 'ajustes.atributos';

    protected $fillable = [
        'nombre_sistema',
        'logo',
        'favicon',
        'color_principal',
        'color_acento',
        'color_principal_claro',
        'color_principal_oscuro',
        'color_texto',
    ];

    protected static function booted(): void
    {
        static::saved(function (Ajuste $ajuste) {
            Cache::forget(self::CACHE_KEY);

            // Remove replaced or cleared images.
            foreach (['logo', 'favicon'] as $campo) {
                $anterior = $ajuste->getOriginal($campo);
                if ($ajuste->wasChanged($campo) && $anterior) {
                    Storage::disk('public')->delete($anterior);
                }
            }
        });
    }

    /**
     * The saved settings, or the institutional defaults when nothing has been saved yet.
     */
    public static function actual(): self
    {
        $atributos = Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->first()?->getAttributes() ?? []);

        return $atributos ? (new static)->newFromBuilder($atributos) : static::porDefecto();
    }

    public static function porDefecto(): self
    {
        return new static(['nombre_sistema' => self::NOMBRE_DEFAULT, ...self::PALETA_INSTITUCIONAL]);
    }

    public function logoUrl(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    public function faviconUrl(): ?string
    {
        return $this->favicon ? Storage::disk('public')->url($this->favicon) : null;
    }

    /** Absolute path of the logo for the PDF, falling back to public/img/logo-capdam.png. */
    public function logoPath(): ?string
    {
        if ($this->logo && Storage::disk('public')->exists($this->logo)) {
            return Storage::disk('public')->path($this->logo);
        }

        $original = public_path('img/logo-capdam.png');

        return is_file($original) ? $original : null;
    }
}
