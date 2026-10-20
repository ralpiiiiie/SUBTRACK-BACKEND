<?php

namespace App\Models;

use App\Services\BillingCalculator;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory, HasUuids;

    public const BILLING_CYCLES = ['weekly', 'monthly', 'yearly'];

    public const CATEGORIES = [
        'entertainment',
        'productivity',
        'fitness',
        'utility',
        'education',
        'gaming',
        'cloud_storage',
        'other',
    ];

    public const STATUSES = ['active', 'paused', 'cancelled'];

    public const CATEGORY_LABELS = [
        'entertainment' => 'Entertainment',
        'productivity'  => 'Productivity',
        'fitness'       => 'Fitness',
        'utility'       => 'Utility',
        'education'     => 'Education',
        'gaming'        => 'Gaming',
        'cloud_storage' => 'Cloud Storage',
        'other'         => 'Other',
    ];

    public const BILLING_CYCLE_LABELS = [
        'weekly'  => 'Weekly',
        'monthly' => 'Monthly',
        'yearly'  => 'Yearly',
    ];

    public const STATUS_LABELS = [
        'active'    => 'Active',
        'paused'    => 'Paused',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'cost',
        'billing_cycle',
        'renewal_date',
        'category',
        'payment_method',
        'status',
        'is_trial',
        'trial_end_date',
        'last_used_date',
        'notes',
    ];

    protected $casts = [
        'cost'           => 'decimal:2',
        'renewal_date'   => 'date',
        'is_trial'       => 'boolean',
        'trial_end_date' => 'date',
        'last_used_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function categoryLabel(): string
    {
        return self::CATEGORY_LABELS[$this->category] ?? $this->category;
    }

    public function statusLabel(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function billingCycleLabel(): string
    {
        return self::BILLING_CYCLE_LABELS[$this->billing_cycle] ?? $this->billing_cycle;
    }

    public function monthlyEquivalent(): float
    {
        return BillingCalculator::monthlyEquivalent((float) $this->cost, $this->billing_cycle);
    }

    public function yearlyEquivalent(): float
    {
        return BillingCalculator::yearlyEquivalent((float) $this->cost, $this->billing_cycle);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('payment_method', 'like', "%{$term}%");
        });
    }

    public function scopeCategory($query, ?string $category)
    {
        return $category ? $query->where('category', $category) : $query;
    }

    public function scopeStatus($query, ?string $status)
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeBillingCycle($query, ?string $billingCycle)
    {
        return $billingCycle ? $query->where('billing_cycle', $billingCycle) : $query;
    }

    public function scopeNotCancelled($query)
    {
        return $query->where('status', '!=', 'cancelled');
    }
}