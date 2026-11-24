<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\User;
use App\Models\Booking;

class DashboardStats extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    protected function getStats(): array
    {
        // 1. Get all active subscribers
        $activeUsers = User::with('plan')
            ->role('subscribed')
            ->where('is_active', true)
            ->get();

        // 2. Calculate Monthly Recurring Revenue (MRR)
        $mrr = 0;
        foreach ($activeUsers as $user) {
            if ($user->plan) {
                if ($user->plan->billing_cycle === 'monthly') {
                    $mrr += $user->plan->price;
                } elseif ($user->plan->billing_cycle === 'every_4_weeks') {
                    $mrr += ($user->plan->price * 52) / 12; // 13 cycles per year / 12 months
                } elseif ($user->plan->billing_cycle === 'annual') {
                    $mrr += $user->plan->price / 12; // Annual divided by 12 months
                }
            }
        }

        // 3. Calculate this month's one-time bookings (Drop-ins)
        $currentMonthBookings = Booking::whereIn('status', ['paid', 'attended'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount_paid');

        $currentMonthRevenue = $mrr + $currentMonthBookings;
        $nextMonthProjected = $mrr; // Baseline projected revenue without future drop-ins

        return [
            Stat::make('Active Subscriptions', $activeUsers->count())
                ->description('Total active members')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
                
            Stat::make('Current Month Revenue', '$' . number_format($currentMonthRevenue, 2))
                ->description('MRR + Drop-in bookings')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
                
            Stat::make('Projected Next Month', '$' . number_format($nextMonthProjected, 2))
                ->description('Guaranteed MRR')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }
}
