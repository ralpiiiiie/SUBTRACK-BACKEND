<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    private function authUser(): User
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'            => 'Netflix',
            'cost'            => 549.00,
            'billing_cycle'   => 'monthly',
            'renewal_date'    => CarbonImmutable::today()->addDays(3)->toDateString(),
            'category'        => 'entertainment',
            'payment_method'  => 'Credit Card',
            'status'          => 'active',
            'is_trial'        => false,
            'notes'           => 'Shared with family',
        ], $overrides);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/subscriptions')->assertStatus(401);
    }

    public function test_creates_a_subscription(): void
    {
        $user = $this->authUser();

        $this->postJson('/api/subscriptions', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Netflix')
            ->assertJsonPath('data.monthly_cost', 549)
            ->assertJsonPath('data.yearly_cost', 6588)
            ->assertJsonPath('data.days_until_renewal', 3)
            ->assertJsonPath('data.renewal_alert', 'due_soon');

        $this->assertDatabaseHas('subscriptions', ['name' => 'Netflix', 'user_id' => $user->id]);
    }

    public function test_weekly_subscription_is_converted_to_monthly_equivalents(): void
    {
        $this->authUser();

        $this->postJson('/api/subscriptions', $this->payload([
            'cost'          => 100.00,
            'billing_cycle' => 'weekly',
        ]))->assertCreated()
            ->assertJsonPath('data.monthly_cost', 433.33)
            ->assertJsonPath('data.yearly_cost', 5200);
    }

    public function test_yearly_subscription_is_converted_to_monthly_equivalents(): void
    {
        $this->authUser();

        $this->postJson('/api/subscriptions', $this->payload([
            'cost'          => 1200.00,
            'billing_cycle' => 'yearly',
        ]))->assertCreated()
            ->assertJsonPath('data.monthly_cost', 100)
            ->assertJsonPath('data.yearly_cost', 1200);
    }

    public function test_rejects_negative_cost(): void
    {
        $this->authUser();

        $this->postJson('/api/subscriptions', $this->payload(['cost' => -5]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('cost');
    }

    public function test_rejects_invalid_billing_cycle(): void
    {
        $this->authUser();

        $this->postJson('/api/subscriptions', $this->payload(['billing_cycle' => 'quarterly']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('billing_cycle');
    }

    public function test_trial_requires_trial_end_date(): void
    {
        $this->authUser();

        $this->postJson('/api/subscriptions', $this->payload([
            'is_trial'        => true,
            'trial_end_date'  => null,
        ]))->assertStatus(422)
            ->assertJsonValidationErrors('trial_end_date');
    }

    public function test_lists_subscriptions_with_category_filter(): void
    {
        $user = $this->authUser();

        Subscription::factory()->count(3)->for($user)->create(['category' => 'entertainment']);
        Subscription::factory()->count(2)->for($user)->create(['category' => 'fitness']);

        $this->getJson('/api/subscriptions?category=entertainment')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_lists_only_your_own_subscriptions(): void
    {
        $user = $this->authUser();
        $otherUser = User::factory()->create();

        Subscription::factory()->for($user)->create(['name' => 'Mine']);
        Subscription::factory()->for($otherUser)->create(['name' => 'Not mine']);

        $this->getJson('/api/subscriptions')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mine');
    }

    public function test_shows_a_subscription(): void
    {
        $user = $this->authUser();

        $subscription = Subscription::factory()->for($user)->create();

        $this->getJson("/api/subscriptions/{$subscription->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $subscription->id);
    }

    public function test_returns_404_for_missing_subscription(): void
    {
        $this->authUser();

        $this->getJson('/api/subscriptions/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }

    public function test_cannot_access_another_users_subscription(): void
    {
        $this->authUser();

        $otherUser = User::factory()->create();
        $subscription = Subscription::factory()->for($otherUser)->create();

        $this->getJson("/api/subscriptions/{$subscription->id}")->assertStatus(404);
        $this->putJson("/api/subscriptions/{$subscription->id}", ['status' => 'paused'])->assertStatus(404);
        $this->deleteJson("/api/subscriptions/{$subscription->id}")->assertStatus(404);
    }

    public function test_updates_a_subscription(): void
    {
        $user = $this->authUser();

        $subscription = Subscription::factory()->for($user)->create([
            'cost'          => 549.00,
            'billing_cycle' => 'monthly',
        ]);

        $this->putJson("/api/subscriptions/{$subscription->id}", [
            'cost'          => 599.00,
            'name'          => 'Netflix Premium',
            'is_trial'      => false,
        ])->assertOk()
            ->assertJsonPath('data.cost', 599)
            ->assertJsonPath('data.monthly_cost', 599);

        $this->assertDatabaseHas('subscriptions', [
            'id'   => $subscription->id,
            'cost' => 599.00,
        ]);
    }

    public function test_update_validates_invalid_data(): void
    {
        $user = $this->authUser();

        $subscription = Subscription::factory()->for($user)->create();

        $this->putJson("/api/subscriptions/{$subscription->id}", ['cost' => -1])
            ->assertStatus(422)
            ->assertJsonValidationErrors('cost');
    }

    public function test_patch_marks_subscription_paused(): void
    {
        $user = $this->authUser();

        $subscription = Subscription::factory()->for($user)->active()->create();

        $this->patchJson("/api/subscriptions/{$subscription->id}", ['status' => 'paused'])
            ->assertOk()
            ->assertJsonPath('data.status', 'paused');
    }

    public function test_deletes_a_subscription(): void
    {
        $user = $this->authUser();

        $subscription = Subscription::factory()->for($user)->create();

        $this->deleteJson("/api/subscriptions/{$subscription->id}")->assertOk();

        $this->assertDatabaseMissing('subscriptions', ['id' => $subscription->id]);
    }

    public function test_deleting_missing_subscription_returns_404(): void
    {
        $this->authUser();

        $this->deleteJson('/api/subscriptions/00000000-0000-0000-0000-000000000000')
            ->assertStatus(404);
    }

    public function test_mark_used_updates_last_used_date(): void
    {
        $user = $this->authUser();

        $subscription = Subscription::factory()->for($user)->create(['last_used_date' => CarbonImmutable::today()->subDays(40)]);

        $this->postJson("/api/subscriptions/{$subscription->id}/mark-used")
            ->assertOk()
            ->assertJsonPath('data.last_used_date', CarbonImmutable::today()->toDateString())
            ->assertJsonPath('data.days_since_last_used', 0);
    }

    public function test_meta_returns_available_values(): void
    {
        $this->authUser();

        $this->getJson('/api/subscriptions/meta')
            ->assertOk()
            ->assertJsonPath('data.categories.0', 'entertainment')
            ->assertJsonPath('data.statuses', ['active', 'paused', 'cancelled']);
    }
}