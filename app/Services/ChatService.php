<?php

namespace App\Services;

/**
 * Orquesta conversaciones privadas: listado, mensajes, envío, menciones y detección de spam.
 */

use App\Events\MessageSent;
use App\Events\UserMentioned;
use App\Models\AppNotification;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Repositories\ChatRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChatService
{
    /**
     * Inyecta repositorio de chat, notificaciones, presencia online y efectos asíncronos.
     */
    public function __construct(
        private readonly ChatRepository $chatRepository,
        private readonly NotificationService $notificationService,
        private readonly OnlinePresenceService $presenceService,
        private readonly AsyncSideEffects $asyncSideEffects,
    ) {}

    /**
     * Lista las conversaciones del usuario formateadas con datos del interlocutor.
     */
    public function getConversations(User $user): Collection
    {
        return $this->chatRepository->getConversationsForUser($user->id)
            ->map(fn (Conversation $c) => $this->formatConversation($c, $user));
    }

    /**
     * Devuelve contactos seguidos con estado online y conversación existente, si la hay.
     */
    public function getContacts(User $user): Collection
    {
        $following = $user->following()
            ->orderByDesc('users.id')
            ->limit(150)
            ->get(['users.id', 'users.username', 'users.avatar_url']);

        if ($following->isEmpty()) {
            return collect();
        }

        // Mapear conversaciones existentes por ID del otro usuario
        $conversations = $this->chatRepository->getConversationsForUser($user->id);
        $conversationByUserId = [];

        foreach ($conversations as $conversation) {
            $other = $conversation->otherUser($user->id);

            if ($other) {
                $conversationByUserId[$other->id] = $conversation;
            }
        }

        $onlineMap = $this->presenceService->onlineMap($following->pluck('id')->all());

        return $following->map(function (User $followed) use ($conversationByUserId, $onlineMap) {
            $conversation = $conversationByUserId[$followed->id] ?? null;

            return [
                'id' => $followed->id,
                'username' => $followed->username,
                'avatar_url' => $followed->avatar_url,
                'is_online' => $onlineMap[$followed->id] ?? false,
                'conversation_id' => $conversation?->id,
                'last_message_at' => $conversation?->last_message_at?->toISOString(),
            ];
        })->sortByDesc(fn (array $contact) => [$contact['is_online'], $contact['last_message_at'] ?? ''])
            ->values();
    }

    /**
     * Actualiza la marca de presencia online del usuario en caché.
     */
    public function touchPresence(User $user): void
    {
        $this->presenceService->touch($user->id);
    }

    /**
     * Obtiene los mensajes de una conversación si el usuario pertenece a ella.
     */
    public function getMessages(User $user, int $conversationId): Collection
    {
        if (! $this->chatRepository->userInConversation($conversationId, $user->id)) {
            throw ValidationException::withMessages([
                'conversation' => ['No tienes acceso a esta conversación.'],
            ]);
        }

        return $this->chatRepository->getMessages($conversationId)
            ->map(fn (Message $m) => $this->formatMessage($m));
    }

    /**
     * Envía un mensaje de texto, actualiza la conversación, notifica y detecta spam.
     */
    public function sendMessage(User $sender, int $conversationId, string $body, string $type = 'text'): Message
    {
        if (! $this->chatRepository->userInConversation($conversationId, $sender->id)) {
            throw ValidationException::withMessages([
                'conversation' => ['No tienes acceso a esta conversación.'],
            ]);
        }

        $body = trim(strip_tags($body));

        if ($body === '') {
            throw ValidationException::withMessages([
                'body' => ['El mensaje no puede estar vacío.'],
            ]);
        }

        $conversation = $this->chatRepository->findConversation($conversationId);
        $message = $this->chatRepository->createMessage($conversationId, $sender->id, $body, $type);

        $conversation?->update(['last_message_at' => now()]);

        $recipient = $conversation?->otherUser($sender->id);

        if ($recipient) {
            broadcast(new MessageSent($message, $recipient->id))->toOthers();
            $this->processMentions($body, $sender, $recipient->id);

            $this->asyncSideEffects->queueNotification(
                $recipient,
                AppNotification::TYPE_MESSAGE_RECEIVED,
                'Nuevo mensaje',
                "@{$sender->username}: ".Str::limit($body, 100),
                '/',
                [
                    'actor_id' => $sender->id,
                    'actor_username' => $sender->username,
                    'conversation_id' => $conversationId,
                ],
            );

            $this->detectSpam($sender);
        }

        return $message->load('user');
    }

    /**
     * Inicia o recupera una conversación privada entre el remitente y el destinatario por username.
     */
    public function startConversation(User $sender, string $recipientUsername): Conversation
    {
        $recipient = $this->chatRepository->findUserByUsername($recipientUsername);

        if (! $recipient) {
            throw ValidationException::withMessages([
                'username' => ['Usuario no encontrado.'],
            ]);
        }

        if ($recipient->id === $sender->id) {
            throw ValidationException::withMessages([
                'username' => ['No puedes chatear contigo mismo.'],
            ]);
        }

        return $this->chatRepository->findOrCreateConversation($sender->id, $recipient->id);
    }

    /**
     * Construye la representación JSON de una conversación para el cliente.
     */
    public function formatConversation(Conversation $conversation, User $currentUser): array
    {
        $other = $conversation->otherUser($currentUser->id);

        return [
            'id' => $conversation->id,
            'last_message_at' => $conversation->last_message_at?->toISOString(),
            'other_user' => $other ? [
                'id' => $other->id,
                'username' => $other->username,
                'avatar_url' => $other->avatar_url,
                'is_online' => $this->presenceService->isOnline($other->id),
            ] : null,
        ];
    }

    /**
     * Construye la representación JSON de un mensaje individual.
     */
    public function formatMessage(Message $message): array
    {
        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'body' => $message->body,
            'type' => $message->type,
            'created_at' => $message->created_at?->toISOString(),
            'user' => [
                'id' => $message->user->id,
                'username' => $message->user->username,
                'avatar_url' => $message->user->avatar_url,
            ],
        ];
    }

    /**
     * Emite eventos de mención (@username) encontrados en el cuerpo del mensaje.
     */
    private function processMentions(string $body, User $sender, int $defaultRecipientId): void
    {
        preg_match_all('/@([a-zA-Z0-9_]+)/', $body, $matches);

        foreach (array_unique($matches[1] ?? []) as $username) {
            $mentioned = $this->chatRepository->findUserByUsername($username);

            if ($mentioned && $mentioned->id !== $sender->id) {
                broadcast(new UserMentioned(
                    $mentioned->id,
                    $sender->username,
                    'chat',
                    Str::limit($body, 80),
                ));
            }
        }
    }

    /**
     * Incrementa contador de mensajes por minuto y alerta al staff si supera el umbral de spam.
     */
    private function detectSpam(User $sender): void
    {
        $countKey = "chat_spam_count:{$sender->id}";
        $alertKey = "chat_spam_alert:{$sender->id}";

        $count = (int) Cache::get($countKey, 0) + 1;
        Cache::put($countKey, $count, now()->addMinute());

        if ($count >= 10 && ! Cache::has($alertKey)) {
            Cache::put($alertKey, true, now()->addMinutes(5));

            $this->notificationService->notifyStaff(
                AppNotification::TYPE_SPAM_DETECTED,
                'Posible spam en mensajes',
                "@{$sender->username} envió {$count} mensajes en menos de un minuto.",
                '/admin/moderacion',
                ['actor_id' => $sender->id, 'message_count' => $count],
            );
        }
    }
}
