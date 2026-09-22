<?php

/**
 * Modelo Eloquent de reacciones emoji sobre publicaciones.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostReaction extends Model
{
    public const TYPES = [
        'like',
        'excelente',
        'lindo',
        'desacuerdo',
        'asombroso',
    ];

    protected $fillable = [
        'post_id',
        'user_id',
        'reaction',
    ];

    /**
     * Publicación que recibió la reacción.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Usuario que reaccionó.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
