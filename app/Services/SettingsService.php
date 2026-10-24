<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\User;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;

class SettingsService
{
    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository
    ) {}

    /**
     * The user's stored preferences merged with the built-in defaults.
     *
     * @return array<string, mixed>
     */
    public function resolvedPreferences(User $user): array
    {
        return $user->preferencesWithDefaults();
    }

    /**
     * Merge a partial set of preferences into the user's stored preferences.
     *
     * @return array<string, mixed>
     */
    public function updatePreferences(User $user, array $data): array
    {
        $user->update([
            'preferences' => array_replace($user->preferences ?? [], $data),
        ]);

        return $this->resolvedPreferences($user);
    }

    /**
     * A complete JSON-serializable snapshot of everything on the account.
     *
     * @return array<string, mixed>
     */
    public function exportData(User $user): array
    {
        return [
            'exported_at' => now()->toIso8601String(),
            'user'        => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'preferences'   => $this->resolvedPreferences($user),
            'subscriptions' => $this->subscriptionRepository->all([], $user->id)
                ->map(fn (Subscription $subscription) => [
                    'id'              => $subscription->id,
                    'name'            => $subscription->name,
                    'cost'            => (float) $subscription->cost,
                    'billing_cycle'   => $subscription->billing_cycle,
                    'renewal_date'    => $subscription->renewal_date?->toDateString(),
                    'category'        => $subscription->category,
                    'payment_method'  => $subscription->payment_method,
                    'status'          => $subscription->status,
                    'is_trial'        => $subscription->is_trial,
                    'trial_end_date'  => $subscription->trial_end_date?->toDateString(),
                    'last_used_date'  => $subscription->last_used_date?->toDateString(),
                    'notes'           => $subscription->notes,
                    'created_at'      => $subscription->created_at?->toIso8601String(),
                    'updated_at'      => $subscription->updated_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Permanently remove the authenticated user and everything associated.
     */
    public function deleteAccount(User $user): void
    {
        $user->subscriptions()->delete();
        $user->tokens()->delete();
        $user->delete();
    }
}