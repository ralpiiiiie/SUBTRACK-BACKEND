<?php

namespace App\Http\Resources;

use App\Services\BillingCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $today = CarbonImmutable::today();

        $daysUntilRenewal = BillingCalculator::daysUntilRenewal(
            $this->renewal_date,
            $today
        );

        $trialDaysRemaining = null;
        $trialAlert = null;
        if ($this->is_trial && $this->trial_end_date) {
            $trialDaysRemaining = BillingCalculator::trialDaysRemaining(
                $this->trial_end_date,
                $today
            );
            $trialAlert = $trialDaysRemaining < 0
                ? 'ended'
                : ($trialDaysRemaining <= 3 ? 'ending_soon' : 'active');
        }

        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'cost'               => (float) $this->cost,
            'billing_cycle'      => $this->billing_cycle,
            'billing_cycle_label'=> $this->billingCycleLabel(),
            'renewal_date'       => $this->renewal_date?->toDateString(),
            'category'           => $this->category,
            'category_label'     => $this->categoryLabel(),
            'payment_method'     => $this->payment_method,
            'status'             => $this->status,
            'status_label'       => $this->statusLabel(),
            'is_trial'           => $this->is_trial,
            'trial_end_date'     => $this->trial_end_date?->toDateString(),
            'last_used_date'     => $this->last_used_date?->toDateString(),
            'notes'              => $this->notes,

            'monthly_cost'       => $this->monthlyEquivalent(),
            'yearly_cost'        => $this->yearlyEquivalent(),
            'days_until_renewal' => $daysUntilRenewal,
            'renewal_alert'      => $this->renewalAlert($daysUntilRenewal),
            'trial_days_remaining' => $trialDaysRemaining,
            'trial_alert'        => $trialAlert,
            'days_since_last_used' => $this->last_used_date
                ? BillingCalculator::daysSince($this->last_used_date, $today)
                : null,

            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }

    private function renewalAlert(int $days): string
    {
        if ($days < 0) {
            return 'overdue';
        }

        if ($days === 0) {
            return 'due_today';
        }

        if ($days <= 3) {
            return 'due_soon';
        }

        if ($days <= 7) {
            return 'approaching';
        }

        return 'later';
    }
}