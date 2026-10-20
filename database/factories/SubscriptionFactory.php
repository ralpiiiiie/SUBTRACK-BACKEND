<?php

namespace Database\Factories;

use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    public function definition(): array
    {
        $billingCycle = fake()->randomElement(Subscription::BILLING_CYCLES);
        $isTrial = fake()->boolean(20);

        return [
            'user_id'         => User::factory(),
            'name'            => fake()->randomElement([
                'Netflix',
                'Spotify',
                'Disney+',
                'YouTube Premium',
                'Google One',
                'iCloud+',
                'Gym Membership',
                'Adobe CC',
                'Notion',
                'Canva Pro',
                'Xbox Game Pass',
                'ChatGPT Plus',
            ]),
            'cost'            => $this->costFor($billingCycle, $isTrial),
            'billing_cycle'   => $billingCycle,
            'renewal_date'    => CarbonImmutable::today()->addDays(fake()->numberBetween(0, 24)),
            'category'        => fake()->randomElement(Subscription::CATEGORIES),
            'payment_method'  => fake()->randomElement(['Credit Card', 'GCash', 'PayPal', 'Debit Card', 'Maya']),
            'status'          => fake()->randomElement(Subscription::STATUSES),
            'is_trial'        => $isTrial,
            'trial_end_date'  => $isTrial ? CarbonImmutable::today()->addDays(fake()->numberBetween(1, 14)) : null,
            'last_used_date'  => fake()->boolean(70)
                ? fake()->dateTimeBetween('-60 days', 'today')->format('Y-m-d')
                : null,
            'notes'           => fake()->optional(0.3)->sentence(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }

    public function paused(): static
    {
        return $this->state(fn () => ['status' => 'paused']);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }

    public function trial(?CarbonImmutable $endDate = null): static
    {
        return $this->state(fn () => [
            'is_trial'       => true,
            'trial_end_date' => ($endDate ?? CarbonImmutable::today()->addDays(5))->toDateString(),
        ]);
    }

    public function billingCycle(string $billingCycle): static
    {
        return $this->state(fn () => ['billing_cycle' => $billingCycle]);
    }

    public function renewalDate(CarbonImmutable $date): static
    {
        return $this->state(fn () => ['renewal_date' => $date->toDateString()]);
    }

    public function status(string $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }

    private function costFor(string $billingCycle, bool $isTrial): float
    {
        if ($isTrial) {
            return round(fake()->randomFloat(2, 0, 50), 2);
        }

        return match ($billingCycle) {
            'weekly'  => round(fake()->randomFloat(2, 50, 300), 2),
            'monthly' => round(fake()->randomFloat(2, 99, 1200), 2),
            'yearly'  => round(fake()->randomFloat(2, 1000, 8000), 2),
            default   => 0,
        };
    }
}