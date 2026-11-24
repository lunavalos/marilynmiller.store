<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\LiveEvent;
use App\Models\Video;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubscriberOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole('subscribed') && !$user->hasAnyRole(['super_admin', 'admin']);
    }

    protected function getStats(): array
    {
        $user = auth()->user()->load('plan');
        $planId = $user->plan_id;

        $coursesCount = Course::where('is_published', true)
            ->where(function ($q) use ($planId) {
                $q->whereDoesntHave('plans');
                if ($planId) {
                    $q->orWhereHas('plans', fn ($p) => $p->where('plans.id', $planId));
                }
            })
            ->count();

        $videosCount = Video::where('is_published', true)
            ->where(function ($q) use ($planId) {
                $q->whereDoesntHave('plans');
                if ($planId) {
                    $q->orWhereHas('plans', fn ($p) => $p->where('plans.id', $planId));
                }
            })
            ->count();

        $upcomingEventsCount = LiveEvent::where('is_published', true)
            ->where(function ($q) use ($planId) {
                $q->whereDoesntHave('plans');
                if ($planId) {
                    $q->orWhereHas('plans', fn ($p) => $p->where('plans.id', $planId));
                }
            })
            ->where('start_time', '>=', now()->subHours(2))
            ->count();

        $bookingsCount = $user->bookings()->count();

        return [
            Stat::make('My Membership Plan', $user->plan?->name_en ?? 'No Plan Assigned')
                ->description($user->is_active ? 'Active Subscription (' . ucfirst($user->plan?->billing_cycle ?? 'Member') . ')' : 'Pending Activation')
                ->descriptionIcon($user->is_active ? 'heroicon-m-check-badge' : 'heroicon-m-clock')
                ->color($user->is_active ? 'success' : 'warning'),

            Stat::make('My Courses', $coursesCount)
                ->description('Included in your membership')
                ->descriptionIcon('heroicon-m-academic-cap')
                ->color('primary'),

            Stat::make('On-Demand Videos', $videosCount)
                ->description('Available to stream anytime')
                ->descriptionIcon('heroicon-m-play-circle')
                ->color('info'),

            Stat::make('Upcoming Live & Bookings', $upcomingEventsCount + $bookingsCount)
                ->description("{$upcomingEventsCount} live events · {$bookingsCount} bookings")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('success'),
        ];
    }
}
