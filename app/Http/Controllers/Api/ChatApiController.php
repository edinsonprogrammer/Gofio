<?php

namespace App\Http\Controllers\Api;

/**
 * API REST de mensajería privada: conversaciones, contactos, presencia y envío de mensajes.
 */

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Http\Requests\SendTipRequest;
use App\Repositories\PostRepository;
use App\Services\ChatService;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatApiController extends Controller
{
    /**
     * Inyecta el servicio de chat para gestionar conversaciones privadas entre usuarios de Gofio.
     */
    public function __construct(
        private readonly ChatService $chatService,
    ) {}

    /**
     * GET /api/chat/conversations — responde con JSON del listado de conversaciones del usuario.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->chatService->getConversations($request->user()),
        ]);
    }

    /**
     * GET /api/chat/contacts — responde con JSON de contactos disponibles para iniciar chat.
     */
    public function contacts(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->chatService->getContacts($request->user()),
        ]);
    }

    /**
     * POST /api/chat/presence — actualiza la presencia del usuario y responde con confirmación.
     */
    public function presence(Request $request): JsonResponse
    {
        $this->chatService->touchPresence($request->user());

        return response()->json(['ok' => true]);
    }

    /**
     * POST /api/chat/conversations — valida el username y responde con JSON de la conversación creada (201).
     */
    public function store(Request $request): JsonResponse
    {
        // Valida que el destinatario exista por nombre de usuario
        $request->validate(['username' => ['required', 'string', 'exists:users,username']]);

        $conversation = $this->chatService->startConversation(
            $request->user(),
            $request->string('username')->toString()
        );

        return response()->json([
            'data' => $this->chatService->formatConversation($conversation, $request->user()),
        ], 201);
    }

    /**
     * GET /api/chat/conversations/{conversation}/messages — responde con JSON del historial de mensajes.
     */
    public function messages(Request $request, int $conversation): JsonResponse
    {
        return response()->json([
            'data' => $this->chatService->getMessages($request->user(), $conversation),
        ]);
    }

    /**
     * POST /api/chat/conversations/{conversation}/messages — valida el mensaje y responde con JSON del mensaje enviado (201).
     */
    public function sendMessage(SendMessageRequest $request, int $conversation): JsonResponse
    {
        $message = $this->chatService->sendMessage(
            $request->user(),
            $conversation,
            $request->validated('body'),
            $request->validated('type') ?? 'text',
        );

        return response()->json([
            'data' => $this->chatService->formatMessage($message),
        ], 201);
    }
}
