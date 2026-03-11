<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
        description: "Retorna mensagens entre o usuário autenticado e outro usuário, com paginação baseada em cursor",
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "userId", in: "path", description: "ID do outro usuário", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "before", in: "query", description: "ID da mensagem mais antiga carregada (cursor)", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "per_page", in: "query", description: "Quantidade por página (máx 50)", required: false, schema: new OA\Schema(type: "integer", default: 20))
        ],
        responses: [
            new OA\Response(response: 200, description: "Lista de mensagens com indicador has_more"),
            new OA\Response(response: 404, description: "Usuário não encontrado")
        ]
    )]
    public function index(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'before' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ]);

        $authUserId = $request->user()->id;
        $perPage = $validated['per_page'] ?? 20;

        $query = Message::betweenUsers($authUserId, $user->id)
            ->with(['sender:id,name', 'receiver:id,name']);

        if (isset($validated['before'])) {
            $query->where('messages.id', '<', $validated['before']);
        }

        $messages = $query->limit($perPage + 1)->get();

        $hasMore = $messages->count() > $perPage;
        if ($hasMore) {
            $messages = $messages->slice(0, $perPage);
        }

        $this->chatService->markAsRead($authUserId, $user->id);

        return response()->json([
            'data' => $messages->values(),
            'has_more' => $hasMore,
        ]);
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
        parameters: [
            new OA\Parameter(name: "page", in: "query", description: "Pagina atual", required: false, schema: new OA\Schema(type: "integer", minimum: 1)),
            new OA\Parameter(name: "per_page", in: "query", description: "Itens por pagina (max 100)", required: false, schema: new OA\Schema(type: "integer", minimum: 1, maximum: 100))
        ],
        responses: [
            new OA\Response(response: 200, description: "Lista de conversas"),
            new OA\Response(response: 401, description: "Não autenticado")
        ]
    )]
    public function conversations(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $userId = $request->user()->id;
        $perPage = (int) ($validated['per_page'] ?? 20);

        $messages = Message::where('sender_id', $userId)
            ->orWhere('receiver_id', $userId)
            ->with(['sender:id,name', 'receiver:id,name'])
            ->orderBy('created_at', 'desc')
            ->get();

        $conversationData = [];
        $orderedKeys = [];

        foreach ($messages as $message) {
            $otherUserId = $message->sender_id === $userId
                ? $message->receiver_id
                : $message->sender_id;

            if (!array_key_exists($otherUserId, $conversationData)) {
                $orderedKeys[] = $otherUserId;
                $otherUser = $message->sender_id === $userId
                    ? $message->receiver
                    : $message->sender;

                $conversationData[$otherUserId] = [
                    'user' => $otherUser,
                    'last_message' => [
                        'id' => $message->id,
                        'content' => $message->content,
                        'is_read' => $message->is_read,
                        'created_at' => $message->created_at,
                    ],
                    'unread_count' => 0,
                ];
            }

            if ($message->receiver_id === $userId && $message->is_read === false) {
                $conversationData[$otherUserId]['unread_count']++;
            }
        }

        $conversationList = [];
        foreach ($orderedKeys as $key) {
            $conversationList[] = $conversationData[$key];
        }

        $page = LengthAwarePaginator::resolveCurrentPage();
        $offset = ($page - 1) * $perPage;
        $items = array_slice($conversationList, $offset, $perPage);

        $paginator = new LengthAwarePaginator(
            $items,
            count($conversationList),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => $request->query(),
            ]
        );

        return response()->json([
            'message' => 'Conversations retrieved successfully.',
            'data' => $paginator,
        ]);
    }

}
