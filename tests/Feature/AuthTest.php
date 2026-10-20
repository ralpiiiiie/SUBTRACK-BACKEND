<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_registers_a_new_user_and_returns_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name'                  => 'King Baltazar',
            'email'                 => 'king@example.com',
            'password'              => 'subtrack-pass123',
            'password_confirmation' => 'subtrack-pass123',
        ])->assertCreated();

        $response->assertJsonPath('message', 'Account created successfully.')
            ->assertJsonPath('data.user.name', 'King Baltazar')
            ->assertJsonPath('data.user.email', 'king@example.com');

        $this->assertArrayHasKey('token', $response->json('data'));
        $this->assertDatabaseHas('users', ['email' => 'king@example.com']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_register_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name'                  => 'Duplicate',
            'email'                 => 'taken@example.com',
            'password'              => 'subtrack-pass123',
            'password_confirmation' => 'subtrack-pass123',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_register_requires_confirmed_password(): void
    {
        $this->postJson('/api/register', [
            'name'                  => 'King Baltazar',
            'email'                 => 'king@example.com',
            'password'              => 'subtrack-pass123',
            'password_confirmation' => 'different-pass',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('password');
    }

    public function test_logs_in_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => 'subtrack-pass123',
        ]);

        $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => 'subtrack-pass123',
        ])->assertOk()
            ->assertJsonPath('data.user.email', 'test@example.com');

        $this->assertDatabaseHas('personal_access_tokens', ['tokenable_id' => $user->id]);
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => 'wrong-password',
        ])->assertStatus(401);
    }

    public function test_login_rejects_unknown_email(): void
    {
        $this->postJson('/api/login', [
            'email'    => 'nobody@example.com',
            'password' => 'whatever123',
        ])->assertStatus(401);
    }

    public function test_returns_authenticated_user(): void
    {
        $user = User::factory()->create([
            'name'  => 'King Baltazar',
            'email' => 'king@example.com',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'King Baltazar')
            ->assertJsonPath('data.email', 'king@example.com');
    }

    public function test_user_endpoint_requires_auth(): void
    {
        $this->getJson('/api/user')->assertStatus(401);
    }

    public function test_logs_out_and_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('subtrack-app')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertDatabaseCount('personal_access_tokens', 0);

        app('auth')->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertStatus(401);
    }
}