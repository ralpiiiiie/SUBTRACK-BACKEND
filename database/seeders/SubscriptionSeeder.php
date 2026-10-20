<?php

namespace Database\Seeders;

use App\Models\Subscription;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => 'password'],
        );

        $today = CarbonImmutable::today();

        $subscriptions = [
            [
                'name' => 'Netflix',
                'cost' => 549.00,
                'billing_cycle' => 'monthly',
                'renewal_date' => $today->addDays(2),
                'category' => 'entertainment',
                'payment_method' => 'Credit Card',
                'status' => 'active',
                'is_trial' => false,
                'last_used_date' => $today->subDays(3),
            ],
            [
                'name' => 'Spotify Premium',
                'cost' => 149.00,
                'billing_cycle' => 'monthly',
                'renewal_date' => $today->addDays(5),
                'category' => 'entertainment',
                'payment_method' => 'GCash',
                'status' => 'active',
                'is_trial' => false,
                'last_used_date' => $today->subDays(40),
            ],
            [
                'name' => 'Disney+',
                'cost' => 249.00,
                'billing_cycle' => 'monthly',
                'renewal_date' => $today->addDays(11),
                'category' => 'entertainment',
                'payment_method' => 'Credit Card',
                'status' => 'active',
                'is_trial' => false,
                'last_used_date' => $today->subDays(6),
            ],
            [
                'name' => 'YouTube Premium',
                'cost' => 1590.00,
                'billing_cycle' => 'yearly',
                'renewal_date' => $today->addDays(60),
                'category' => 'entertainment',
                'payment_method' => 'GCash',
                'status' => 'active',
                'is_trial' => false,
                'last_used_date' => $today->subDays(1),
            ],
            [
                'name' => 'Google One',
                'cost' => 119.00,
                'billing_cycle' => 'monthly',
                'renewal_date' => $today->addDays(1),
                'category' => 'cloud_storage',
                'payment_method' => 'PayPal',
                'status' => 'active',
                'is_trial' => false,
                'last_used_date' => $today->subDays(10),
            ],
            [
                'name' => '24/7 Fitness Gym',
                'cost' => 800.00,
                'billing_cycle' => 'monthly',
                'renewal_date' => $today->addDays(4),
                'category' => 'fitness',
                'payment_method' => 'Debit Card',
                'status' => 'active',
                'is_trial' => false,
                'last_used_date' => $today->subDays(2),
            ],
            [
                'name' => 'ChatGPT Plus',
                'cost' => 800.00,
                'billing_cycle' => 'monthly',
                'renewal_date' => $today->addDays(13),
                'category' => 'productivity',
                'payment_method' => 'Credit Card',
                'status' => 'active',
                'is_trial' => false,
                'last_used_date' => $today->subDays(0),
            ],
            [
                'name' => 'Adobe Creative Cloud',
                'cost' => 400.00,
                'billing_cycle' => 'monthly',
                'renewal_date' => $today->addDays(21),
                'category' => 'productivity',
                'payment_method' => 'Credit Card',
                'status' => 'paused',
                'is_trial' => false,
                'last_used_date' => $today->subDays(45),
            ],
            [
                'name' => 'Xbox Game Pass',
                'cost' => 599.00,
                'billing_cycle' => 'monthly',
                'renewal_date' => $today->addDays(18),
                'category' => 'gaming',
                'payment_method' => 'Maya',
                'status' => 'cancelled',
                'is_trial' => false,
                'last_used_date' => $today->subDays(60),
            ],
            [
                'name' => 'iCloud+ Free Trial',
                'cost' => 0.00,
                'billing_cycle' => 'monthly',
                'renewal_date' => $today->addDays(6),
                'category' => 'cloud_storage',
                'payment_method' => 'None',
                'status' => 'active',
                'is_trial' => true,
                'trial_end_date' => $today->addDays(2),
                'last_used_date' => $today->subDays(0),
            ],
            [
                'name' => 'Notion Plus Trial',
                'cost' => 300.00,
                'billing_cycle' => 'yearly',
                'renewal_date' => $today->addDays(30),
                'category' => 'productivity',
                'payment_method' => 'PayPal',
                'status' => 'active',
                'is_trial' => true,
                'trial_end_date' => $today->addDays(9),
                'last_used_date' => $today->subDays(1),
            ],
        ];

        foreach ($subscriptions as $subscription) {
            Subscription::query()->updateOrCreate(
                ['name' => $subscription['name'], 'user_id' => $user->id],
                $subscription,
            );
        }
    }
}