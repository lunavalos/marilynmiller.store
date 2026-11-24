<?php

namespace App\Filament\Widgets;

use App\Models\Course;
use App\Models\LiveEvent;
use App\Models\Session;
use App\Models\Video;
use Filament\Widgets\Widget;

class SubscriberDashboardWidget extends Widget
{
    protected static string $view = 'filament.widgets.subscriber-dashboard-widget';
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();
        return $user && $user->hasRole('subscribed') && !$user->hasAnyRole(['super_admin', 'admin']);
    }

    protected function getViewData(): array
    {
        $user = auth()->user()->load('plan');
        $planId = $user->plan_id;

        $courses = Course::where('is_published', true)
            ->where(function ($q) use ($planId) {
                $q->whereDoesntHave('plans');
                if ($planId) {
                    $q->orWhereHas('plans', fn ($p) => $p->where('plans.id', $planId));
                }
            })
            ->withCount('modules')
            ->latest()
            ->take(6)
            ->get();

        $videos = Video::where('is_published', true)
            ->where(function ($q) use ($planId) {
                $q->whereDoesntHave('plans');
                if ($planId) {
                    $q->orWhereHas('plans', fn ($p) => $p->where('plans.id', $planId));
                }
            })
            ->latest()
            ->take(6)
            ->get();

        $liveEvents = LiveEvent::where('is_published', true)
            ->where(function ($q) use ($planId) {
                $q->whereDoesntHave('plans');
                if ($planId) {
                    $q->orWhereHas('plans', fn ($p) => $p->where('plans.id', $planId));
                }
            })
            ->orderBy('start_time', 'asc')
            ->take(4)
            ->get();

        $sessions = Session::where(function ($q) use ($planId) {
                $q->whereDoesntHave('plans');
                if ($planId) {
                    $q->orWhereHas('plans', fn ($p) => $p->where('plans.id', $planId));
                }
            })
            ->with('service')
            ->orderBy('start_time', 'asc')
            ->take(4)
            ->get();

        return [
            'user' => $user,
            'plan' => $user->plan,
            'courses' => $courses,
            'videos' => $videos,
            'liveEvents' => $liveEvents,
            'sessions' => $sessions,
        ];
    }
}
