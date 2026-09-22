<?php

/**
 * Modelo Eloquent de paquetes de iconos personalizados para rangos y medallas.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IconPack extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'source',
        'description',
        'author',
        'version',
        'mappings',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'mappings' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Mapa de iconos personalizados para rangos.
     */
    public function rankMappings(): array
    {
        return $this->mappings['ranks'] ?? [];
    }

    /**
     * Mapa de iconos personalizados para medallas.
     */
    public function medalMappings(): array
    {
        return $this->mappings['medals'] ?? [];
    }
}
