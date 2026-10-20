<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardApiTest extends TestCase
{
    use RefreshDatabase;

    private function authUser(): User
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        return $user;
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/dashboard')->assertStatus(401);
    }

    public function test_dashboard_returns_aggregated_spending(): void
    {
        $user = $this->authUser();

        Subscription::factory()->for($user)->active()->create([
            'name'          => 'Netflix',
            'cost'          => 500.00,
            'billing_cycle' => 'monthly',
            'category'      => 'entertainment',
            'renewal_date'  => CarbonImmutable::today()->addDays(2),
        ]);

        Subscription::factory()->for($user)->active()->create([
            'name'          => 'YouTube Premium',
            'cost'          => 1200.00,
            'billing_cycle' => 'yearly',
            'category'      => 'entertainment',
            'renewal_date'  => CarbonImmutable::today()->addDays(10),
        ]);

        Subscription::factory()->for($user)->cancelled()->create([
            'name'          => 'Old Gym',
            'cost'          => 999.00,
            'billing_cycle' => 'monthly',
            'category'      => 'fitness',
            'renewal_date'  => CarbonImmutable::today()->addDays(15),
        ]);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_monthly', 600)
            ->assertJsonPath('data.total_yearly', 7200)
            ->assertJsonPath('data.active_count', 2)
            ->assertJsonPath('data.cancelled_count', 1)
            ->assertJsonPath('data.due_soon_count', 1);
    }

    public function test_dashboard_excludes_cancelled_from_active_spending(): void
    {
        $user = $this->authUser();

        Subscription::factory()->for($user)->cancelled()->create([
            'cost'          => 5000.00,
            'billing_cycle' => 'monthly',
        ]);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_monthly', 0)
            ->assertJsonPath('data.total_yearly', 0);
    }

    public function test_dashboard_list_spending_by_category(): void
    {
        $user = $this->authUser();

        Subscription::factory()->for($user)->active()->count(2)->create([
            'cost'          => 100.00,
            'billing_cycle' => 'monthly',
            'category'      => 'entertainment',
        ]);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonFragment([
                'category' => 'entertainment',
                'label'    => 'Entertainment',
                'monthly'  => 200,
                'yearly'   => 2400,
                'count'    => 2,
            ]);
    }

    public function test_dashboard_only_counts_your_own_subscriptions(): void
    {
        $user = $this->authUser();

        Subscription::factory()->for($user)->active()->create([
            'cost'          => 500.00,
            'billing_cycle' => 'monthly',
        ]);

        $otherUser = User::factory()->create();
        Subscription::factory()->for($otherUser)->active()->create([
            'cost'          => 99999.99,
            'billing_cycle' => 'monthly',
        ]);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total_monthly', 500);
    }

    public function test_upcoming_renewals_flags_due_soon_and_overdue(): void
    {
        $user = $this->authUser();

        Subscription::factory()->for($user)->active()->create([
            'name'          => 'Due in 2 days',
            'renewal_date'  => CarbonImmutable::today()->addDays(2),
        ]);
        Subscription::factory()->for($user)->active()->create([
            'name'          => 'Overdue',
            'renewal_date'  => CarbonImmutable::today()->subDays(1),
        ]);
        Subscription::factory()->for($user)->active()->create([
            'name'          => 'Far away',
            'renewal_date'  => CarbonImmutable::today()->addDays(20),
        ]);

        $this->getJson('/api/dashboard/upcoming-renewals?days=7')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Overdue')
            ->assertJsonPath('data.0.days_until_renewal', -1)
            ->assertJsonPath('data.0.renewal_alert', 'overdue')
            ->assertJsonPath('data.1.days_until_renewal', 2)
            ->assertJsonPath('data.1.renewal_alert', 'due_soon');
    }

    public function test_dashboard_tracks_active_trials_and_remaining_days(): void
    {
        $user = $this->authUser();

        Subscription::factory()->for($user)->active()->trial(CarbonImmutable::today()->addDays(2))->create();

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.trial_count', 1)
            ->assertJsonPath('data.trials.0.is_trial', true)
            ->assertJsonPath('data.trials.0.trial_days_remaining', 2)
            ->assertJsonPath('data.trials.0.trial_alert', 'ending_soon');
    }

    public function test_dashboard_flags_ended_trials(): void
    {
        $user = $this->authUser();

        Subscription::factory()->for($user)->active()->trial(CarbonImmutable::today()->subDays(1))->create();

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.trials.0.trial_alert', 'ended')
            ->assertJsonPath('data.trials.0.trial_days_remaining', -1);
    }

    public function test_dashboard_suggests_unused_subscriptions(): void
    {
        $user = $this->authUser();

        Subscription::factory()->for($user)->active()->create([
            'name'           => 'Spotify Premium',
            'last_used_date' => CarbonImmutable::today()->subDays(45),
        ]);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.unused_suggestions.0.name', 'Spotify Premium')
            ->assertJsonPath('data.unused_suggestions.0.days_since_last_used', 45);
    }
}