<?php

/**
 * Canales de broadcasting en tiempo real: notificaciones privadas y chat.
 * Define quién puede suscribirse a cada canal de WebSockets.
 */

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Canal privado del modelo User (convención Laravel)
Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return (int) $user->id === (int) $id;
});

// Canal privado de notificaciones por usuario
Broadcast::channel('user.{id}', function (User $user, int $id) {
    return (int) $user->id === (int) $id;
});

// Canal de chat: solo participantes de la conversación
Broadcast::channel('chat.{conversationId}', function (User $user, int $conversationId) {
    return Conversation::query()
        ->where('id', $conversationId)
        ->where(function ($q) use ($user) {
            $q->where('user_one_id', $user->id)->orWhere('user_two_id', $user->id);
        })
        ->exists();
});
