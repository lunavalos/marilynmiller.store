<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Revenue Forecast (Past & Projected)';
    protected static ?int $sort = 3;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    protected function getData(): array
    {
        $labels = [];
        $data = [];

        // We will calculate for: 3 months ago, 2 months ago, 1 month ago, current, +1, +2, +3
        for ($i = -3; $i <= 3; $i++) {
            $targetDate = now()->addMonths($i);
            $monthEnd = $targetDate->copy()->endOfMonth();
            
            $labels[] = $targetDate->format('M Y');

            // 1. Calculate MRR for that month
            // We assume a user was paying in that month if they were created before or during that month
            // and they are currently active. (Simplified MRR without a dedicated payments table).
            $activeUsersInMonth = User::with('plan')
                ->whereNotNull('plan_id')
                ->where('is_active', true)
                ->where('created_at', '<=', $monthEnd)
                ->get();

            $mrr = 0;
            foreach ($activeUsersInMonth as $user) {
                if ($user->plan) {
                    if ($user->plan->billing_cycle === 'monthly') {
                        $mrr += $user->plan->price;
                    } elseif ($user->plan->billing_cycle === 'every_4_weeks') {
                        $mrr += ($user->plan->price * 52) / 12;
                    } elseif ($user->plan->billing_cycle === 'annual') {
                        $mrr += $user->plan->price / 12;
                    }
                }
            }

            // 2. Calculate Drop-in Bookings for that month (only for past and current months)
            $bookingsRevenue = 0;
            if ($i <= 0) {
                $bookingsRevenue = Booking::whereIn('status', ['paid', 'attended'])
                    ->whereMonth('created_at', $targetDate->month)
                    ->whereYear('created_at', $targetDate->year)
                    ->sum('amount_paid');
            }

            $data[] = round($mrr + $bookingsRevenue, 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (USD)',
                    'data' => $data,
                    'backgroundColor' => '#10b981',
                    'borderColor' => '#10b981',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
