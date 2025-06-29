<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected $authUser;

    public function setUp(): void
    {
        parent::setUp();

        $this->authUser = User::factory()->create();
    }

    public function test_user_can_be_listed()
    {
        User::factory()->count(3)->create();

        $response = $this->actingAs($this->authUser)->getJson('/api/users');

        $response->assertStatus(200)->assertJsonCount(4); // 3 + 1 auth user
    }

    public function test_user_can_be_created()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
        ];

        $response = $this->actingAs($this->authUser)->postJson('/api/users', $data);

        $response->assertStatus(201)->assertJsonFragment([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }

    public function test_user_can_be_shown()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->authUser)->getJson("/api/users/{$user->id}");

        $response->assertStatus(200)->assertJsonFragment([
            'email' => $user->email,
        ]);
    }

    public function test_user_can_be_updated()
    {
        $user = User::factory()->create();

        $data = ['name' => 'Updated Name'];

        $response = $this->actingAs($this->authUser)->putJson("/api/users/{$user->id}", $data);

        $response->assertStatus(200)->assertJsonFragment(['name' => 'Updated Name']);
    }

    public function test_user_can_be_deleted()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->authUser)->deleteJson("/api/users/{$user->id}");

        $response->assertStatus(200)->assertJson(['message' => 'User deleted successfully']);
    }
}
