<?php

/**
 * Modelo Eloquent de publicaciones.
 * Almacena contenido en bloques, métricas de interacción, estado de publicación y metadatos de moderación.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    protected $with = ['user.rango', 'category'];

    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'slug',
        'content',
        'points_count',
        'comments_count',
        'views_count',
        'tips_total',
        'tips_count',
        'status',
        'is_featured',
        'is_sticky',
        'featured_at',
        'featured_by',
        'tags',
        'block_comments',
        'is_private',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'points_count' => 'integer',
            'comments_count' => 'integer',
            'views_count' => 'integer',
            'tips_total' => 'decimal:2',
            'tips_count' => 'integer',
            'is_featured' => 'boolean',
            'is_sticky' => 'boolean',
            'block_comments' => 'boolean',
            'is_private' => 'boolean',
            'featured_at' => 'datetime',
        ];
    }

    /**
     * Autor de la publicación.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Categoría temática del post.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Comentarios asociados a la publicación.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Usuario del staff que destacó la publicación.
     */
    public function featuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'featured_by');
    }

    /**
     * Scope que filtra posts publicados y no privados.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('is_private', false);
    }
}
