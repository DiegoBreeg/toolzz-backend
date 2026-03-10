<?php

namespace Tests\Unit;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_belongs_to_sender(): void
    {
        $user = User::factory()->create();
        $message = Message::factory()->create(['sender_id' => $user->id]);

        $this->assertInstanceOf(User::class, $message->sender);
        $this->assertEquals($user->id, $message->sender->id);
    }

    public function test_message_belongs_to_receiver(): void
    {
        $user = User::factory()->create();
        $message = Message::factory()->create(['receiver_id' => $user->id]);

        $this->assertInstanceOf(User::class, $message->receiver);
        $this->assertEquals($user->id, $message->receiver->id);
    }

    public function test_scope_between_users_returns_correct_messages(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Message::factory()->count(3)->create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
        ]);

        Message::factory()->count(2)->create([
            'sender_id' => $user2->id,
            'receiver_id' => $user1->id,
        ]);

        Message::factory()->create([
            'sender_id' => $user1->id,
            'receiver_id' => $user3->id,
        ]);

        $messages = Message::betweenUsers($user1->id, $user2->id)->get();

        $this->assertCount(5, $messages);

        foreach ($messages as $message) {
            $this->assertTrue(
                ($message->sender_id === $user1->id && $message->receiver_id === $user2->id) ||
                ($message->sender_id === $user2->id && $message->receiver_id === $user1->id)
            );
        }
    }

    public function test_scope_between_users_orders_by_most_recent(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $oldMessage = Message::factory()->create([
            'sender_id' => $user1->id,
            'receiver_id' => $user2->id,
            'created_at' => now()->subDays(2),
        ]);

        $newMessage = Message::factory()->create([
            'sender_id' => $user2->id,
            'receiver_id' => $user1->id,
            'created_at' => now(),
        ]);

        $messages = Message::betweenUsers($user1->id, $user2->id)->get();

        $this->assertEquals($newMessage->id, $messages->first()->id);
        $this->assertEquals($oldMessage->id, $messages->last()->id);
    }

    public function test_is_read_is_cast_to_boolean(): void
    {
        $message = Message::factory()->create(['is_read' => false]);

        $this->assertIsBool($message->is_read);
        $this->assertFalse($message->is_read);

        $message->update(['is_read' => true]);
        $message->refresh();

        $this->assertTrue($message->is_read);
    }
}
