<?php

namespace App\Repositories;

/**
 * Persistencia y consulta de packs de iconos registrados en base de datos.
 */

use App\Models\IconPack;
use Illuminate\Database\Eloquent\Collection;

class IconPackRepository
{
    /**
     * Busca un pack activo por su slug único.
     */
    public function findBySlug(string $slug): ?IconPack
    {
        return IconPack::query()->where('slug', $slug)->where('is_active', true)->first();
    }

    /**
     * Obtiene todos los packs ordenados por nombre para administración.
     */
    public function getAllForAdmin(): Collection
    {
        return IconPack::query()->orderBy('name')->get();
    }

    /**
     * Crea o actualiza un pack importado desde carpeta; devuelve el modelo y si fue creado.
     */
    public function upsertFolderPack(string $slug, array $attributes): array
    {
        $existing = IconPack::query()->where('slug', $slug)->first();

        if ($existing) {
            $existing->update($attributes);

            return [$existing->fresh(), false];
        }

        $created = IconPack::create([
            ...$attributes,
            'slug' => $slug,
            'is_active' => true,
        ]);

        return [$created, true];
    }
}
