<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * The chat service instance.
     */
    private ChatService $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * List messages between the authenticated user and another user.
     */
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
}
