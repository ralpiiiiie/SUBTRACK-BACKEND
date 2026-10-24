<?php

namespace App\Repositories\Contracts;

use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface SubscriptionRepositoryInterface
{
    public function paginate(array $filters, int $perPage = 20, ?int $userId = null): LengthAwarePaginator;

    public function all(array $filters = [], ?int $userId = null): Collection;

    public function find(string $id, ?int $userId = null): ?Subscription;

    public function create(array $data): Subscription;

    public function update(Subscription $subscription, array $data): Subscription;

    public function delete(Subscription $subscription): bool;
}