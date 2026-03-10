<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ChatController extends Controller
{
    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * List messages between the authenticated user and another user.
     */
    #[OA\Get(
        path: "/messages/{userId}",
        tags: ["Chat"],
        summary: "Listar mensagens",
        description: "Retorna mensagens entre o usuário autenticado e outro usuário",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "userId", in: "path", description: "ID do outro usuário", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Lista de mensagens paginada"),
            new OA\Response(response: 404, description: "Usuário não encontrado")
        ]
    )]
    public function index(Request $request, User $user): JsonResponse
    {
        $authUserId = $request->user()->id;

        $messages = Message::betweenUsers($authUserId, $user->id)
            ->with(['sender:id,name', 'receiver:id,name'])
            ->paginate(50);

        $this->chatService->markAsRead($authUserId, $user->id);

        return response()->json($messages);
    }

    /**
     * Send a new message.
     */
    #[OA\Post(
        path: "/messages",
        tags: ["Chat"],
        summary: "Enviar mensagem",
        description: "Envia uma mensagem para outro usuário. Transmitida em tempo real via WebSocket.",
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["receiver_id", "content"],
                properties: [
                    new OA\Property(property: "receiver_id", type: "integer", description: "ID do destinatário"),
                    new OA\Property(property: "content", type: "string", description: "Conteúdo da mensagem (máx 5000 chars)")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Mensagem enviada"),
            new OA\Response(response: 422, description: "Erro de validação")
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'content' => ['required', 'string', 'max:5000'],
        ]);

        if ($validated['receiver_id'] === $request->user()->id) {
            return response()->json([
                'message' => 'You cannot send messages to yourself.',
            ], 422);
        }

        $message = $this->chatService->sendMessage(
            $request->user()->id,
            $validated['receiver_id'],
            $validated['content']
        );

        $message->load(['sender:id,name', 'receiver:id,name']);

        return response()->json([
            'message' => 'Message sent successfully.',
            'data' => $message,
        ], 201);
    }

    /**
     * Search messages for the authenticated user.
     */
    #[OA\Get(
        path: "/search/messages",
        tags: ["Chat"],
        summary: "Buscar mensagens",
        description: "Busca mensagens do usuário usando Meilisearch",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "q", in: "query", description: "Termo de busca", required: true, schema: new OA\Schema(type: "string", minLength: 1, maxLength: 255))
        ],
        responses: [
            new OA\Response(response: 200, description: "Resultados da busca"),
            new OA\Response(response: 422, description: "Parâmetro inválido")
        ]
    )]
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $userId = $request->user()->id;
        $query = $validated['q'];

        $messages = Message::search($query)
            ->query(function ($builder) use ($userId) {
                $builder->where(function ($q) use ($userId) {
                    $q->where('sender_id', $userId)
                      ->orWhere('receiver_id', $userId);
                })->with(['sender:id,name', 'receiver:id,name']);
            })
            ->paginate(20);

        return response()->json([
            'message' => 'Search completed successfully.',
            'data' => $messages,
        ]);
    }

    /**
     * List all conversations for the authenticated user.
     */
    #[OA\Get(
        path: "/conversations",
        tags: ["Chat"],
        summary: "Listar conversas",
        description: "Retorna todas as conversas do usuário com última mensagem e não lidas",
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Lista de conversas"),
            new OA\Response(response: 401, description: "Não autenticado")
        ]
    )]
    public function conversations(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $conversations = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender:id,name', 'receiver:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(function ($message) use ($userId) {
                return $message->sender_id === $userId
                    ? $message->receiver_id
                    : $message->sender_id;
            })
            ->map(function ($messages) use ($userId) {
                $lastMessage = $messages->first();
                $otherUser = $lastMessage->sender_id === $userId
                    ? $lastMessage->receiver
                    : $lastMessage->sender;

                return [
                    'user' => $otherUser,
                    'last_message' => [
                        'id' => $lastMessage->id,
                        'content' => $lastMessage->content,
                        'is_read' => $lastMessage->is_read,
                        'created_at' => $lastMessage->created_at,
                    ],
                    'unread_count' => $messages->where('receiver_id', $userId)
                        ->where('is_read', false)
                        ->count(),
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Conversations retrieved successfully.',
            'data' => $conversations,
        ]);
    }
}
