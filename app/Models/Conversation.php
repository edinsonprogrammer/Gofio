<?php

/**
 * Modelo Eloquent de conversaciones privadas entre dos usuarios.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = [
        'user_one_id',
        'user_two_id',
        'last_message_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * Primer participante de la conversación (menor ID).
     */
    public function userOne(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_one_id');
    }

    /**
     * Segundo participante de la conversación (mayor ID).
     */
    public function userTwo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_two_id');
    }

    /**
     * Mensajes intercambiados en la conversación.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Devuelve el interlocutor distinto al usuario dado.
     */
    public function otherUser(int $userId): ?User
    {
        if ($this->user_one_id === $userId) {
            return $this->userTwo;
        }

        if ($this->user_two_id === $userId) {
            return $this->userOne;
        }

        return null;
    }

    /**
     * Busca la conversación existente entre dos usuarios, normalizando el orden de IDs.
     */
    public function findBetween(int $userA, int $userB): ?self
    {
        [$one, $two] = $userA < $userB ? [$userA, $userB] : [$userB, $userA];

        return self::query()
            ->where('user_one_id', $one)
            ->where('user_two_id', $two)
            ->first();
    }
}
