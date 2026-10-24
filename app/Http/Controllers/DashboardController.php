<?php

namespace App\Http\Controllers;

use App\Http\Resources\SubscriptionResource;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $dashboard = $this->subscriptionService->dashboard($request->user()?->id);

        return response()->json([
            'data' => [
                'total_monthly'        => $dashboard['total_monthly'],
                'total_yearly'         => $dashboard['total_yearly'],
                'paused_monthly'       => $dashboard['paused_monthly'],
                'active_count'         => $dashboard['active_count'],
                'paused_count'         => $dashboard['paused_count'],
                'cancelled_count'      => $dashboard['cancelled_count'],
                'total_count'          => $dashboard['total_count'],
                'trial_count'          => $dashboard['trial_count'],
                'due_soon_count'       => $dashboard['due_soon_count'],
                'upcoming_renewals'    => SubscriptionResource::collection($dashboard['upcoming_renewals']),
                'trials'               => SubscriptionResource::collection($dashboard['trials']),
                'unused_suggestions'   => SubscriptionResource::collection($dashboard['unused_suggestions']),
                'spending_by_category' => $dashboard['spending_by_category'],
                'recent_subscriptions' => SubscriptionResource::collection($dashboard['recent_subscriptions']),
            ],
        ]);
    }

    public function upcomingRenewals(Request $request): JsonResponse
    {
        $days = max(1, min((int) $request->query('days', 7), 60));

        $renewals = $this->subscriptionService->upcomingRenewals($days, 10, $request->user()?->id);

        return response()->json([
            'data' => SubscriptionResource::collection($renewals),
        ]);
    }
}