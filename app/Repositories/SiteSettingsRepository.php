<?php

namespace App\Repositories;

/**
 * Lee y escribe ajustes globales del sitio con caché en memoria y valores por defecto fusionados.
 */

use App\Models\AdminActionLog;
use App\Models\SiteSetting;
use App\Support\SiteSettingsDefaults;
use Illuminate\Support\Facades\Cache;

class SiteSettingsRepository
{
    private const CACHE_KEY = 'gofio:site_settings';

    private const CACHE_TTL_SECONDS = 300;

    /**
     * Devuelve todos los ajustes fusionando defaults del sistema con valores persistidos en caché.
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL_SECONDS, function () {
            $stored = SiteSetting::query()->get()->mapWithKeys(fn ($s) => [$s->key => $s->value])->all();

            return array_merge(SiteSettingsDefaults::all(), $stored);
        });
    }

    /**
     * Obtiene un ajuste por clave con fallback al valor por defecto indicado.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Guarda o actualiza un ajuste individual e invalida la caché global.
     */
    public function set(string $key, mixed $value): void
    {
        $this->persist($key, $value);
        $this->flushCache();
    }

    /**
     * Persiste varios ajustes en lote e invalida la caché una sola vez al finalizar.
     */
    public function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            $this->persist($key, $value);
        }

        $this->flushCache();
    }

    /**
     * Persiste un valor o elimina la clave si es null (la columna JSON no admite NULL).
     */
    private function persist(string $key, mixed $value): void
    {
        if ($value === null) {
            SiteSetting::query()->where('key', $key)->delete();

            return;
        }

        SiteSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value],
        );
    }

    /**
     * Elimina la entrada de caché para forzar recarga desde base de datos en la próxima lectura.
     */
    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
