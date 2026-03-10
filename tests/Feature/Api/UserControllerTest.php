<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_list_other_users(): void
    {
        User::factory()->count(5)->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'data' => [
                        '*' => ['id', 'name', 'email', 'created_at'],
                    ],
                ],
            ]);

        // Should not include the authenticated user
        $users = collect($response->json('data.data'));
        $this->assertFalse($users->contains('id', $this->user->id));
    }

    public function test_user_can_view_another_user_profile(): void
    {
        $otherUser = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/users/' . $otherUser->id);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'User retrieved successfully.',
                'data' => [
                    'id' => $otherUser->id,
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                ],
            ]);
    }

    public function test_user_can_update_their_profile(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user', [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile updated successfully.',
                'data' => [
                    'name' => 'Updated Name',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_user_can_update_their_email(): void
    {
        $response = $this->actingAs($this->user)
            ->putJson('/api/user', [
                'email' => 'newemail@example.com',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => 'newemail@example.com',
        ]);
    }

    public function test_user_cannot_update_email_to_existing_email(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'taken@example.com',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson('/api/user', [
                'email' => 'taken@example.com',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_can_delete_their_account(): void
    {
        $userId = $this->user->id;

        $response = $this->actingAs($this->user)
            ->deleteJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Account deleted successfully.',
            ]);

        $this->assertDatabaseMissing('users', [
            'id' => $userId,
        ]);
    }

    public function test_unauthenticated_user_cannot_access_users(): void
    {
        $response = $this->getJson('/api/users');

        $response->assertStatus(401);
    }

    public function test_user_can_get_their_own_profile(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ]);
    }
}
