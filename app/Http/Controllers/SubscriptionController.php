<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionRequest;
use App\Http\Requests\UpdateSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['search', 'category', 'status', 'billing_cycle', 'sort', 'sort_dir']);
        $perPage = min((int) $request->query('per_page', 50), 100);

        $subscriptions = $this->subscriptionService->list($filters, $perPage, $request->user()?->id);

        return response()->json([
            'data' => SubscriptionResource::collection($subscriptions->items()),
            'meta' => [
                'current_page' => $subscriptions->currentPage(),
                'per_page'     => $subscriptions->perPage(),
                'total'        => $subscriptions->total(),
                'last_page'    => $subscriptions->lastPage(),
            ],
        ]);
    }

    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->create($request->validated(), $request->user()?->id);

        return response()->json([
            'message' => 'Subscription created successfully.',
            'data'    => new SubscriptionResource($subscription),
        ], 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $subscription = $this->subscriptionService->find($id, $request->user()?->id);

        if (!$subscription) {
            return $this->notFound();
        }

        return response()->json(['data' => new SubscriptionResource($subscription)]);
    }

    public function update(UpdateSubscriptionRequest $request, string $id): JsonResponse
    {
        $subscription = $this->subscriptionService->find($id, $request->user()?->id);

        if (!$subscription) {
            return $this->notFound();
        }

        $updated = $this->subscriptionService->update($subscription, $request->validated());

        return response()->json([
            'message' => 'Subscription updated successfully.',
            'data'    => new SubscriptionResource($updated),
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $subscription = $this->subscriptionService->find($id, $request->user()?->id);

        if (!$subscription) {
            return $this->notFound();
        }

        $this->subscriptionService->delete($subscription);

        return response()->json([
            'message' => 'Subscription deleted successfully.',
        ], 200);
    }

    public function meta(): JsonResponse
    {
        return response()->json(['data' => $this->subscriptionService->meta()]);
    }

    public function markUsed(Request $request, string $id): JsonResponse
    {
        $subscription = $this->subscriptionService->find($id, $request->user()?->id);

        if (!$subscription) {
            return $this->notFound();
        }

        $updated = $this->subscriptionService->markUsed($subscription);

        return response()->json([
            'message' => 'Last used date updated.',
            'data'    => new SubscriptionResource($updated),
        ]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['message' => 'Subscription not found.'], 404);
    }
}