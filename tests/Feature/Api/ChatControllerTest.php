<?php

namespace Tests\Feature\Api;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    public function test_user_can_send_message(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/messages', [
                'receiver_id' => $this->otherUser->id,
                'content' => 'Hello, how are you?',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'sender_id',
                    'receiver_id',
                    'content',
                    'created_at',
                ],
            ]);

        $this->assertDatabaseHas('messages', [
            'sender_id' => $this->user->id,
            'receiver_id' => $this->otherUser->id,
            'content' => 'Hello, how are you?',
            'is_read' => false,
        ]);
    }

    public function test_user_cannot_send_message_to_themselves(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/messages', [
                'receiver_id' => $this->user->id,
                'content' => 'Hello myself!',
            ]);

        $response->assertStatus(422);
    }

    public function test_user_can_list_messages_with_another_user(): void
    {
        // Create some messages
        Message::factory()->count(5)->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $this->otherUser->id,
        ]);

        Message::factory()->count(3)->create([
            'sender_id' => $this->otherUser->id,
            'receiver_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/messages/' . $this->otherUser->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'has_more',
            ]);

        $this->assertCount(8, $response->json('data'));
    }

    public function test_user_can_list_conversations(): void
    {
        // Create messages with multiple users
        $thirdUser = User::factory()->create();

        Message::factory()->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $this->otherUser->id,
            'content' => 'Last message to user 2',
        ]);

        Message::factory()->create([
            'sender_id' => $thirdUser->id,
            'receiver_id' => $this->user->id,
            'content' => 'Message from user 3',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/conversations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'user' => ['id', 'name'],
                            'last_message' => ['id', 'content', 'is_read', 'created_at'],
                            'unread_count',
                        ],
                    ],
                ],
            ]);

        $this->assertCount(2, $response->json('data.data'));
    }

    public function test_unauthenticated_user_cannot_access_messages(): void
    {
        $response = $this->postJson('/api/messages', [
            'receiver_id' => $this->otherUser->id,
            'content' => 'Hello!',
        ]);

        $response->assertStatus(401);
    }

    public function test_message_validation_fails_without_content(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/messages', [
                'receiver_id' => $this->otherUser->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_message_validation_fails_with_invalid_receiver(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/messages', [
                'receiver_id' => 99999,
                'content' => 'Hello!',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['receiver_id']);
    }

    public function test_user_can_search_messages(): void
    {
        Message::factory()->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $this->otherUser->id,
            'content' => 'Laravel is awesome',
        ]);

        Message::factory()->create([
            'sender_id' => $this->otherUser->id,
            'receiver_id' => $this->user->id,
            'content' => 'I agree, Laravel rocks',
        ]);

        // Message from other users should not appear
        $thirdUser = User::factory()->create();
        Message::factory()->create([
            'sender_id' => $thirdUser->id,
            'receiver_id' => $this->otherUser->id,
            'content' => 'Laravel hidden message',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/search/messages?q=Laravel');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => ['data'],
            ]);
    }

    public function test_search_requires_query_parameter(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/search/messages');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['q']);
    }

    public function test_cursor_pagination_on_messages(): void
    {
        $messages = Message::factory()->count(5)->create([
            'sender_id' => $this->user->id,
            'receiver_id' => $this->otherUser->id,
        ]);

        $lastMessageId = $messages->sortByDesc('id')->first()->id;

        $response = $this->actingAs($this->user)
            ->getJson("/api/messages/{$this->otherUser->id}?before={$lastMessageId}&per_page=2");

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'has_more']);
    }
}
