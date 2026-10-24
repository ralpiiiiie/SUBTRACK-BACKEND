<?php

namespace App\Repositories;

use App\Models\Subscription;
use App\Repositories\Contracts\SubscriptionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20, ?int $userId = null): LengthAwarePaginator
    {
        return $this->applyFilters(Subscription::query(), $filters, $userId)
            ->orderBy($this->sortColumn($filters), $this->sortDirection($filters))
            ->paginate($perPage)
            ->appends(array_filter($filters));
    }

    public function all(array $filters = [], ?int $userId = null): Collection
    {
        return $this->applyFilters(Subscription::query(), $filters, $userId)
            ->orderBy($this->sortColumn($filters), $this->sortDirection($filters))
            ->get();
    }

    public function find(string $id, ?int $userId = null): ?Subscription
    {
        $query = Subscription::query();

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->find($id);
    }

    public function create(array $data): Subscription
    {
        return Subscription::create($data);
    }

    public function update(Subscription $subscription, array $data): Subscription
    {
        $subscription->update($data);

        return $subscription->fresh();
    }

    public function delete(Subscription $subscription): bool
    {
        return (bool) $subscription->delete();
    }

    private function applyFilters($query, array $filters, ?int $userId)
    {
        $query = $query
            ->search($filters['search'] ?? null)
            ->category($filters['category'] ?? null)
            ->status($filters['status'] ?? null)
            ->billingCycle($filters['billing_cycle'] ?? null);

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query;
    }

    private function sortColumn(array $filters): string
    {
        return match ($filters['sort'] ?? 'renewal_date') {
            'name'         => 'name',
            'cost'         => 'cost',
            'created_at'   => 'created_at',
            'billing_cycle'=> 'billing_cycle',
            default        => 'renewal_date',
        };
    }

    private function sortDirection(array $filters): string
    {
        return ($filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
    }
}