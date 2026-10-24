<?php

namespace App\Services;

use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SubscriptionService
{
    public const UPCOMING_DAYS = 7;
    public const UNUSED_THRESHOLD_DAYS = 30;

    public function __construct(
        private readonly SubscriptionRepositoryInterface $subscriptionRepository
    ) {}

    public function list(array $filters, int $perPage = 20, ?int $userId = null): LengthAwarePaginator
    {
        return $this->subscriptionRepository->paginate($filters, $perPage, $userId);
    }

    public function find(string $id, ?int $userId = null): ?Subscription
    {
        return $this->subscriptionRepository->find($id, $userId);
    }

    public function create(array $data, ?int $userId = null): Subscription
    {
        if ($userId !== null) {
            $data['user_id'] = $userId;
        }

        return $this->subscriptionRepository->create($this->normalize($data));
    }

    public function update(Subscription $subscription, array $data): Subscription
    {
        return $this->subscriptionRepository->update($subscription, $this->normalize($data));
    }

    public function delete(Subscription $subscription): bool
    {
        return $this->subscriptionRepository->delete($subscription);
    }

    public function markUsed(Subscription $subscription): Subscription
    {
        return $this->subscriptionRepository->update($subscription, [
            'last_used_date' => CarbonImmutable::today()->toDateString(),
        ]);
    }

    public function meta(): array
    {
        return [
            'billing_cycles' => Subscription::BILLING_CYCLES,
            'categories'     => Subscription::CATEGORIES,
            'statuses'       => Subscription::STATUSES,
            'category_labels' => Subscription::CATEGORY_LABELS,
            'billing_cycle_labels' => Subscription::BILLING_CYCLE_LABELS,
            'status_labels'  => Subscription::STATUS_LABELS,
            'payment_methods' => [
                'Credit Card',
                'Debit Card',
                'GCash',
                'Maya',
                'PayPal',
                'Bank Transfer',
                'e-Wallet',
                'Apple Pay',
                'Google Pay',
            ],
        ];
    }

    public function upcomingRenewals(int $days = self::UPCOMING_DAYS, int $limit = 10, ?int $userId = null): Collection
    {
        return $this->subscriptionRepository->all([], $userId)
            ->filter(fn (Subscription $subscription) => $subscription->status !== 'cancelled')
            ->filter(fn (Subscription $subscription) => $this->renewalIsNear($subscription, $days))
            ->sortBy(fn (Subscription $subscription) => $subscription->renewal_date)
            ->take($limit)
            ->values();
    }

    public function dashboard(?int $userId = null): array
    {
        $all = $this->subscriptionRepository->all([], $userId);
        $today = CarbonImmutable::today();

        $active = $all->where('status', 'active');
        $paused = $all->where('status', 'paused');

        $monthly = (float) $active->sum(fn (Subscription $s) => $s->monthlyEquivalent());
        $yearly = (float) $active->sum(fn (Subscription $s) => $s->yearlyEquivalent());
        $pausedMonthly = (float) $paused->sum(fn (Subscription $s) => $s->monthlyEquivalent());

        $upcoming = $all
            ->filter(fn (Subscription $s) => $s->status !== 'cancelled')
            ->filter(fn (Subscription $s) => $this->renewalIsNear($s, self::UPCOMING_DAYS))
            ->sortBy('renewal_date')
            ->take(5)
            ->values();

        $trials = $all
            ->filter(fn (Subscription $s) => $s->is_trial && $s->status === 'active')
            ->sortBy('trial_end_date')
            ->values();

        $unused = $all
            ->filter(fn (Subscription $s) => $s->status !== 'cancelled')
            ->filter(fn (Subscription $s) => $this->isUnused($s, $today))
            ->sortByDesc('last_used_date')
            ->take(3)
            ->values();

        return [
            'total_monthly'        => round($monthly, 2),
            'total_yearly'         => round($yearly, 2),
            'paused_monthly'       => round($pausedMonthly, 2),
            'active_count'         => $active->count(),
            'paused_count'         => $paused->count(),
            'cancelled_count'      => $all->where('status', 'cancelled')->count(),
            'total_count'          => $all->count(),
            'trial_count'          => $trials->count(),
            'due_soon_count'       => $upcoming->count(),
            'upcoming_renewals'    => $upcoming,
            'trials'               => $trials,
            'unused_suggestions'   => $unused,
            'spending_by_category' => $this->spendingByCategory($active),
            'recent_subscriptions' => $all->sortByDesc('created_at')->take(5)->values(),
        ];
    }

    public function spendingByCategory(Collection $subscriptions): array
    {
        return collect(Subscription::CATEGORIES)
            ->map(function (string $category) use ($subscriptions) {
                $items = $subscriptions->where('category', $category);

                return [
                    'category' => $category,
                    'label'    => Subscription::CATEGORY_LABELS[$category] ?? $category,
                    'monthly'  => round((float) $items->sum(fn (Subscription $s) => $s->monthlyEquivalent()), 2),
                    'yearly'   => round((float) $items->sum(fn (Subscription $s) => $s->yearlyEquivalent()), 2),
                    'count'    => $items->count(),
                ];
            })
            ->reject(fn (array $row) => $row['count'] === 0)
            ->values()
            ->sortByDesc('monthly')
            ->values()
            ->all();
    }

    /**
     * Keep optional companion fields consistent with what the user actually set.
     */
    private function normalize(array $data): array
    {
        if (isset($data['is_trial']) && !$data['is_trial']) {
            $data['trial_end_date'] = null;
        }

        return $data;
    }

    private function renewalIsNear(Subscription $subscription, int $days): bool
    {
        $remaining = BillingCalculator::daysUntilRenewal(
            CarbonImmutable::parse($subscription->renewal_date)
        );

        return $remaining <= $days;
    }

    private function isUnused(Subscription $subscription, CarbonImmutable $today): bool
    {
        if (!$subscription->last_used_date) {
            return $subscription->created_at?->lessThan($today->subDays(self::UNUSED_THRESHOLD_DAYS)) ?? false;
        }

        $daysSince = BillingCalculator::daysSince(
            CarbonImmutable::parse($subscription->last_used_date),
            $today
        );

        return $daysSince >= self::UNUSED_THRESHOLD_DAYS;
    }
}