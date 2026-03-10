<?php

namespace App\Services;

use App\Events\MessageSent;
use App\Models\Message;

class ChatService
{
    /**
     * Send a new message from one user to another.
     */
    public function sendMessage(int $senderId, int $receiverId, string $content): Message
    {
        $message = Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'content' => $content,
        ]);

        $message->load('sender');

        event(new MessageSent($message));

        return $message;
    }

    /**
     * Mark messages as read.
     */
    public function markAsRead(int $userId, int $otherUserId): int
    {
        return Message::where('sender_id', $otherUserId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }
}
