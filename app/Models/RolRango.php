<?php

/**
 * Modelo Eloquent de rangos de usuario.
 * Define poder de voto, límites diarios, permisos de publicación y privilegios de staff.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RolRango extends Model
{
    protected $table = 'roles_rangos';

    protected $fillable = [
        'nombre',
        'slug',
        'color',
        'icon',
        'puntos_requeridos',
        'poder_voto',
        'limite_voto_diario',
        'is_staff',
        'auto_promote',
        'post_permissions',
        'admin_permissions',
    ];

    /**
     * Genera slug único automáticamente al crear un rango sin slug.
     */
    protected static function booted(): void
    {
        static::creating(function (RolRango $rango) {
            if (blank($rango->slug)) {
                $rango->slug = $rango->uniqueSlugFrom($rango->nombre);
            }
        });
    }

    /**
     * Construye un slug único a partir del nombre del rango.
     */
    private function uniqueSlugFrom(string $name): string
    {
        $base = Str::slug($name) ?: 'rango';
        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    protected function casts(): array
    {
        return [
            'puntos_requeridos' => 'integer',
            'poder_voto' => 'integer',
            'limite_voto_diario' => 'integer',
            'is_staff' => 'boolean',
            'auto_promote' => 'boolean',
            'post_permissions' => 'array',
            'admin_permissions' => 'array',
        ];
    }

    /**
     * Usuarios que poseen este rango.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'rango_id');
    }
}
