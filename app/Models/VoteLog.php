<?php

/**
 * Modelo Eloquent del registro de votos emitidos sobre posts o comentarios.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteLog extends Model
{
    public $timestamps = false;

    protected $table = 'votes_logs';

    protected $fillable = [
        'user_id',
        'post_id',
        'comment_id',
        'points_given',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'points_given' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Usuario que emitió el voto.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Post votado, si aplica.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Comentario votado, si aplica.
     */
    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }
}
