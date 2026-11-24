<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Plan;

class SubscriptionsByPlanChart extends ChartWidget
{
    protected static ?string $heading = 'Subscriptions by Plan';
    protected static ?int $sort = 2;

    public static function canView(): bool
    {
        return auth()->user()?->hasAnyRole(['super_admin', 'admin']) ?? false;
    }

    protected function getData(): array
    {
        $plans = Plan::withCount(['users' => function ($query) {
            $query->where('is_active', true);
        }])->get();

        $labels = [];
        $data = [];
        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#06b6d4'];

        foreach ($plans as $plan) {
            if ($plan->users_count > 0) {
                $labels[] = $plan->name_en;
                $data[] = $plan->users_count;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Active Subscribers',
                    'data' => $data,
                    'backgroundColor' => array_slice($colors, 0, count($data)),
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
