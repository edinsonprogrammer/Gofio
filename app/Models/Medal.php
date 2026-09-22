<?php

/**
 * Modelo Eloquent de medallas de gamificación.
 * Pueden otorgarse automáticamente por condiciones o manualmente por el staff.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Medal extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'icon',
        'color',
        'condition_type',
        'condition_value',
        'is_active',
        'sort_order',
    ];

    /**
     * Genera slug único automáticamente al crear una medalla sin slug.
     */
    protected static function booted(): void
    {
        static::creating(function (Medal $medal) {
            if (blank($medal->slug)) {
                $medal->slug = $medal->uniqueSlugFrom($medal->title);
            }
        });
    }

    /**
     * Construye un slug único a partir del título de la medalla.
     */
    private function uniqueSlugFrom(string $title): string
    {
        $base = Str::slug($title) ?: 'medalla';
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
            'is_active' => 'boolean',
            'condition_value' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Usuarios que poseen esta medalla.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['granted_by', 'note', 'granted_at']);
    }
}
