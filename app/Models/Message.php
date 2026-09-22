<?php

/**
 * Modelo Eloquent de mensajes dentro de una conversación privada.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'user_id',
        'body',
        'type',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * Conversación a la que pertenece el mensaje.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Usuario remitente del mensaje.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
