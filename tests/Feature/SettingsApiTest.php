<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingsApiTest extends TestCase
{
    use RefreshDatabase;

    private function authUser(): User
    {
        $user = User::factory()->create([
            'name'  => 'King Baltazar',
            'email' => 'king@example.com',
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_settings_endpoint_requires_auth(): void
    {
        $this->getJson('/api/settings')->assertStatus(401);
    }

    public function test_returns_user_with_default_preferences(): void
    {
        $user = $this->authUser();

        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.name', 'King Baltazar')
            ->assertJsonPath('data.user.email', 'king@example.com')
            ->assertJsonPath('data.preferences.renewal_reminders', true)
            ->assertJsonPath('data.preferences.trial_reminders', true)
            ->assertJsonPath('data.preferences.expired_alerts', true)
            ->assertJsonPath('data.preferences.reminder_days', 3)
            ->assertJsonPath('data.preferences.default_billing_cycle', 'monthly')
            ->assertJsonPath('data.preferences.default_currency', 'PHP');
    }

    public function test_updates_profile(): void
    {
        $user = $this->authUser();

        $this->patchJson('/api/settings/profile', [
            'name'  => 'King B. Baltazar',
            'email' => 'kingb@example.com',
        ])->assertOk()
            ->assertJsonPath('message', 'Profile updated successfully.')
            ->assertJsonPath('data.name', 'King B. Baltazar')
            ->assertJsonPath('data.email', 'kingb@example.com');

        $this->assertDatabaseHas('users', [
            'id'    => $user->id,
            'name'  => 'King B. Baltazar',
            'email' => 'kingb@example.com',
        ]);
    }

    public function test_profile_rejects_email_taken_by_another_user(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $this->authUser();

        $this->patchJson('/api/settings/profile', [
            'name'  => 'King Baltazar',
            'email' => 'taken@example.com',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_updates_notification_preferences_and_merges_defaults(): void
    {
        $user = $this->authUser();

        $this->patchJson('/api/settings/preferences', [
            'renewal_reminders' => false,
            'reminder_days'     => 7,
        ])->assertOk()
            ->assertJsonPath('data.renewal_reminders', false)
            ->assertJsonPath('data.trial_reminders', true)
            ->assertJsonPath('data.expired_alerts', true)
            ->assertJsonPath('data.reminder_days', 7)
            ->assertJsonPath('data.default_currency', 'PHP');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
        ]);

        $this->assertFalse($user->fresh()->preferences['renewal_reminders']);
        $this->assertSame(7, $user->fresh()->preferences['reminder_days']);
    }

    public function test_rejects_invalid_preferences(): void
    {
        $this->authUser();

        $this->patchJson('/api/settings/preferences', [
            'reminder_days'         => 0,
            'default_billing_cycle' => 'fortnightly',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['reminder_days', 'default_billing_cycle']);
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $this->authUser();

        $this->patchJson('/api/settings/password', [
            'current_password'      => 'wrong-password',
            'password'              => 'new-secure-pass1',
            'password_confirmation' => 'new-secure-pass1',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'The current password is incorrect.')
            ->assertJsonValidationErrors('current_password');
    }

    public function test_changes_password_and_allows_login_with_new_password(): void
    {
        $user = User::factory()->create([
            'email'    => 'king@example.com',
            'password' => 'original-pass123',
        ]);
        Sanctum::actingAs($user);

        $this->patchJson('/api/settings/password', [
            'current_password'      => 'original-pass123',
            'password'              => 'new-secure-pass1',
            'password_confirmation' => 'new-secure-pass1',
        ])->assertOk()
            ->assertJsonPath('message', 'Password updated successfully.');

        $this->postJson('/api/login', [
            'email'    => 'king@example.com',
            'password' => 'new-secure-pass1',
        ])->assertOk();

        $this->postJson('/api/login', [
            'email'    => 'king@example.com',
            'password' => 'original-pass123',
        ])->assertStatus(401);
    }

    public function test_exports_own_data_as_attached_json(): void
    {
        $user = $this->authUser();
        Subscription::factory()->for($user)->active()->create([
            'name' => 'Netflix',
        ]);

        $response = $this->getJson('/api/settings/export');
        $response->assertOk();
        $this->assertStringContainsString('attachment', $response->headers->get('content-disposition') ?? '');

        $json = json_decode($response->streamedContent(), true);
        $this->assertSame($user->email, $json['user']['email']);
        $this->assertCount(1, $json['subscriptions']);
        $this->assertSame('Netflix', $json['subscriptions'][0]['name']);
    }

    public function test_deletes_only_the_authenticated_account_and_its_data(): void
    {
        $user = User::factory()->create(['email' => 'doomed@example.com']);
        Subscription::factory()->for($user)->active()->count(3)->create();
        $token = $user->createToken('subtrack-app')->plainTextToken;

        $other = User::factory()->create(['email' => 'safe@example.com']);
        Subscription::factory()->for($other)->active()->create();

        $this->withToken($token)->deleteJson('/api/account')
            ->assertOk()
            ->assertJsonPath('message', 'Account deleted successfully.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseCount('subscriptions', 1);
        $this->assertDatabaseMissing('subscriptions', ['user_id' => $user->id]);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        app('auth')->forgetGuards();

        $this->withToken($token)->getJson('/api/user')->assertStatus(401);
    }
}