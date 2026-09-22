<?php

/**
 * Modelo Eloquent de categorías temáticas para clasificar publicaciones.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'icon',
    ];

    /**
     * Publicaciones clasificadas en esta categoría.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
