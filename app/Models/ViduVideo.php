<?php

/**
 * Modelo Eloquent de videos cortos Vidu.
 * Representa un clip vertical de hasta 1 minuto (3 min para creadores verificados).
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ViduVideo extends Model
{
    protected $table = 'vidu_videos';

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'video_path',
        'video_url',
        'thumbnail_url',
        'duration_seconds',
        'likes_count',
        'saves_count',
        'views_count',
        'status',
        'is_private',
        'ads_override',
    ];

    protected function casts(): array
    {
        return [
            'duration_seconds' => 'integer',
            'likes_count'      => 'integer',
            'saves_count'      => 'integer',
            'views_count'      => 'integer',
            'is_private'       => 'boolean',
            'ads_override'     => 'boolean',
        ];
    }

    /**
     * Autor del video.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usuarios que dieron like a este video.
     */
    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vidu_likes', 'video_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Usuarios que guardaron este video.
     */
    public function savedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'vidu_saves', 'video_id', 'user_id')
            ->withTimestamps();
    }

    /**
     * Scope: solo videos activos y públicos.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', 'active')->where('is_private', false);
    }
}
