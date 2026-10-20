<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class BillingCalculator
{
    public const WEEKS_PER_YEAR = 52;
    public const MONTHS_PER_YEAR = 12;

    public static function monthlyEquivalent(float $cost, string $billingCycle): float
    {
        return round(match ($billingCycle) {
            'weekly'  => $cost * (self::WEEKS_PER_YEAR / self::MONTHS_PER_YEAR),
            'monthly' => $cost,
            'yearly'  => $cost / self::MONTHS_PER_YEAR,
            default   => 0.0,
        }, 2);
    }

    public static function yearlyEquivalent(float $cost, string $billingCycle): float
    {
        return round(match ($billingCycle) {
            'weekly'  => $cost * self::WEEKS_PER_YEAR,
            'monthly' => $cost * self::MONTHS_PER_YEAR,
            'yearly'  => $cost,
            default   => 0.0,
        }, 2);
    }

    /**
     * Signed number of whole days from today until the renewal date.
     * Negative values mean the subscription is overdue.
     */
    public static function daysUntilRenewal(CarbonInterface $renewalDate, ?CarbonInterface $today = null): int
    {
        $today = self::normalizedToday($today);

        return (int) $today->diffInDays(CarbonImmutable::parse($renewalDate)->startOfDay(), false);
    }

    /**
     * Number of whole days remaining until the trial ends (0 when it expires today).
     */
    public static function trialDaysRemaining(CarbonInterface $trialEndDate, ?CarbonInterface $today = null): int
    {
        $today = self::normalizedToday($today);

        return (int) $today->diffInDays(CarbonImmutable::parse($trialEndDate)->startOfDay(), false);
    }

    public static function daysSince(CarbonInterface $date, ?CarbonInterface $today = null): int
    {
        $today = self::normalizedToday($today);

        return (int) CarbonImmutable::parse($date)
            ->startOfDay()
            ->diffInDays($today, false);
    }

    private static function normalizedToday(?CarbonInterface $today): CarbonImmutable
    {
        $base = $today ?? CarbonImmutable::today();

        return CarbonImmutable::parse($base)->startOfDay();
    }
}