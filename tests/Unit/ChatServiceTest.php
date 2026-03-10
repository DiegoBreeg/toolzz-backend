<?php

namespace Tests\Unit;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ChatServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatService $chatService;
    private User $sender;
    private User $receiver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->chatService = new ChatService();
        $this->sender = User::factory()->create();
        $this->receiver = User::factory()->create();
    }

    public function test_send_message_creates_message_in_database(): void
    {
        Event::fake();

        $message = $this->chatService->sendMessage(
            $this->sender->id,
            $this->receiver->id,
            'Hello, World!'
        );

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals($this->sender->id, $message->sender_id);
        $this->assertEquals($this->receiver->id, $message->receiver_id);
        $this->assertEquals('Hello, World!', $message->content);

        $message->refresh();
        $this->assertFalse($message->is_read);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'content' => 'Hello, World!',
        ]);
    }

    public function test_send_message_dispatches_event(): void
    {
        Event::fake();

        $message = $this->chatService->sendMessage(
            $this->sender->id,
            $this->receiver->id,
            'Hello!'
        );

        Event::assertDispatched(MessageSent::class, function ($event) use ($message) {
            return $event->message->id === $message->id;
        });
    }

    public function test_mark_as_read_updates_messages(): void
    {
        Message::factory()->count(3)->create([
            'sender_id' => $this->sender->id,
            'receiver_id' => $this->receiver->id,
            'is_read' => false,
        ]);

        $anotherUser = User::factory()->create();
        Message::factory()->create([
            'sender_id' => $anotherUser->id,
            'receiver_id' => $this->receiver->id,
            'is_read' => false,
        ]);

        $count = $this->chatService->markAsRead($this->receiver->id, $this->sender->id);

        $this->assertEquals(3, $count);

        $this->assertEquals(0, Message::where('sender_id', $this->sender->id)
            ->where('receiver_id', $this->receiver->id)
            ->where('is_read', false)
            ->count());

        $this->assertEquals(1, Message::where('sender_id', $anotherUser->id)
            ->where('is_read', false)
            ->count());
    }
}
