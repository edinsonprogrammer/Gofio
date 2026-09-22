<?php

/**
 * Modelo Eloquent de temas visuales.
 * Puede provenir de la base de datos o de carpetas en /themes.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'source',
        'description',
        'author',
        'version',
        'variables',
        'custom_css',
        'icon_pack_slug',
        // Variante estructural del layout: default | sidebar-nav | minimal | compact | panel
        'layout_variant',
        // URL de logo personalizado para el tema
        'logo_url',
        'is_default',
        'requires_creator_plus',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_default' => 'boolean',
            'requires_creator_plus' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Usuarios que tienen seleccionado este tema.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Devuelve el mapa de variables CSS del tema.
     */
    public function cssVariables(): array
    {
        return $this->variables ?? [];
    }

    /**
     * Indica si el tema se carga desde una carpeta en /themes.
     */
    public function isFolderTheme(): bool
    {
        return $this->source === 'folder';
    }
}
