<?php

namespace App\Repositories;

/**
 * Acceso a datos de conversaciones y mensajes del chat privado.
 */

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ChatRepository
{
    /**
     * Lista conversaciones del usuario ordenadas por último mensaje, con participantes cargados.
     */
    public function getConversationsForUser(int $userId): Collection
    {
        return Conversation::query()
            ->with(['userOne', 'userTwo'])
            ->where(function ($q) use ($userId) {
                $q->where('user_one_id', $userId)->orWhere('user_two_id', $userId);
            })
            ->orderByDesc('last_message_at')
            ->limit(80)
            ->get();
    }

    /**
     * Busca una conversación por ID con relaciones de ambos participantes.
     */
    public function findConversation(int $id): ?Conversation
    {
        return Conversation::with(['userOne', 'userTwo'])->find($id);
    }

    /**
     * Obtiene conversación existente entre dos usuarios o crea una nueva con IDs ordenados.
     */
    public function findOrCreateConversation(int $userA, int $userB): Conversation
    {
        $existing = Conversation::findBetween($userA, $userB);

        if ($existing) {
            return $existing;
        }

        [$one, $two] = $userA < $userB ? [$userA, $userB] : [$userB, $userA];

        return Conversation::create([
            'user_one_id' => $one,
            'user_two_id' => $two,
        ]);
    }

    /**
     * Obtiene los últimos mensajes de una conversación en orden cronológico ascendente.
     */
    public function getMessages(int $conversationId, int $limit = 50): Collection
    {
        return Message::query()
            ->with('user')
            ->where('conversation_id', $conversationId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    /**
     * Inserta un nuevo mensaje en la conversación indicada.
     */
    public function createMessage(int $conversationId, int $userId, string $body, string $type = 'text'): Message
    {
        return Message::create([
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'body' => $body,
            'type' => $type,
        ]);
    }

    /**
     * Comprueba si el usuario es participante de la conversación.
     */
    public function userInConversation(int $conversationId, int $userId): bool
    {
        return Conversation::query()
            ->where('id', $conversationId)
            ->where(function ($q) use ($userId) {
                $q->where('user_one_id', $userId)->orWhere('user_two_id', $userId);
            })
            ->exists();
    }

    /**
     * Busca un usuario por username para iniciar conversaciones o resolver menciones.
     */
    public function findUserByUsername(string $username): ?User
    {
        return User::where('username', $username)->first();
    }
}
