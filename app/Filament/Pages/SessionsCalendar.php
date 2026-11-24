<?php

namespace App\Filament\Pages;

use App\Filament\Resources\SessionResource;
use App\Models\Booking;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Session;
use App\Models\User;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;

class SessionsCalendar extends Page
{
    use \App\Filament\Concerns\HasResourcePermissions;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationGroup = 'Operations';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Sessions Calendar';
    protected static ?string $navigationLabel = 'Calendar';
    protected static ?string $slug = 'calendar';

    protected static string $view = 'filament.pages.sessions-calendar';

    public ?string $filterEventType = null;
    public ?int $newAttendeeUserId = null;

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function getEvents(): array
    {
        $user = auth()->user();
        $isSub = static::isSubscriber();
        $planId = $user?->plan_id;

        $query = Session::with(['service', 'plans', 'bookings.user']);

        if ($this->filterEventType) {
            $query->where('event_type', $this->filterEventType);
        }

        if ($isSub) {
            $query->where(function ($q) use ($planId) {
                $q->whereDoesntHave('plans');
                if ($planId) {
                    $q->orWhereHas('plans', fn ($p) => $p->where('plans.id', $planId));
                }
                $q->orWhereNotNull('stripe_payment_link');
            });
        }

        $sessions = $query->get();

        return $sessions->map(function (Session $session) use ($user) {
            $isBooked = $user ? $session->bookings->contains('user_id', $user->id) : false;
            $serviceName = $session->service?->name ?? 'Class';
            $isFull = $session->is_full;

            // Brand color scheme: #3CA7A1 primary teal, #e11d48 full red, #059669 booked emerald
            $color = '#3CA7A1';
            if ($isFull) {
                $color = '#e11d48';
            } elseif ($isBooked) {
                $color = '#059669';
            }

            $userHasIncludedPlan = false;
            if ($user && $user->plan_id) {
                $userHasIncludedPlan = $session->plans->isEmpty() || $session->plans->contains('id', $user->plan_id);
            }

            return [
                'id' => (string) $session->id,
                'title' => $session->title,
                'start' => $session->start_time?->toIso8601String(),
                'end' => $session->end_time ? $session->end_time->toIso8601String() : $session->start_time?->addHour()->toIso8601String(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'session_id' => $session->id,
                    'service_id' => $session->service_id,
                    'event_type' => $session->event_type ?: 'live_in_person',
                    'event_type_label' => $session->event_type_label,
                    'title' => $session->title,
                    'service_name' => $session->event_type_label,
                    'start_formatted' => $session->start_time?->format('D, M d, Y · g:i A'),
                    'end_formatted' => $session->end_time?->format('g:i A'),
                    'start_time_iso' => $session->start_time?->format('Y-m-d\TH:i'),
                    'end_time_iso' => $session->end_time?->format('Y-m-d\TH:i'),
                    'location_or_link' => $session->location_or_link,
                    'max_seats' => $session->max_seats,
                    'booked_seats' => $session->booked_seats_count,
                    'available_seats' => $session->available_seats,
                    'is_full' => $isFull,
                    'is_booked' => $isBooked,
                    'is_past' => (bool) (($session->end_time ?? $session->start_time)?->isPast()),
                    'user_has_included_plan' => $userHasIncludedPlan,
                    'is_recurring' => (bool) $session->is_recurring,
                    'parent_session_id' => $session->parent_session_id,
                    'is_part_of_series' => (bool) ($session->is_recurring || $session->parent_session_id),
                    'recurrence_rule_raw' => $session->recurrence_rule,
                    'recurrence_end_date_raw' => $session->recurrence_end_date?->format('Y-m-d'),
                    'recurrence_rule' => match($session->recurrence_rule) {
                        'daily' => 'Daily (Diaria)',
                        'weekly' => 'Weekly - Every 7 days (Semanal)',
                        'biweekly' => 'Every 2 Weeks - 14 days (Cada dos semanas)',
                        'every_4_weeks' => 'Every 4 Weeks - 28 days (Cada cuatro semanas)',
                        'monthly' => 'Monthly (Mensual)',
                        default => $session->parent_session_id ? 'Recurring Instance' : null,
                    },
                    'drop_in_price' => $session->drop_in_price ? number_format($session->drop_in_price, 2) : null,
                    'stripe_payment_link' => $session->stripe_payment_link,
                    'plan_ids' => $session->plans->pluck('id')->toArray(),
                    'plans' => $session->plans->pluck('name_en')->toArray(),
                    'edit_url' => SessionResource::getUrl('edit', ['record' => $session->id]),
                    'attendees' => $session->bookings->map(fn ($b) => [
                        'id' => $b->id,
                        'name' => $b->user?->name ?? 'Guest Client',
                        'email' => $b->user?->email,
                        'status' => $b->status,
                        'date' => $b->created_at?->format('M d, g:i A'),
                    ])->values()->toArray(),
                ],
            ];
        })->values()->toArray();
    }

    public function filterByEventType(?string $eventType = null): void
    {
        $this->filterEventType = $eventType;
        $this->dispatch('calendar-refresh', events: $this->getEvents());
    }

    public function filterByService(?int $serviceId = null): void
    {
        $this->dispatch('calendar-refresh', events: $this->getEvents());
    }

    public function saveSession(array $data): void
    {
        if (static::isSubscriber()) {
            abort(403);
        }

        $sessionId = !empty($data['id']) ? (int) $data['id'] : null;
        $planIds = $data['plans'] ?? [];

        $startTime = Carbon::parse($data['start_time']);

        // Prevent creating new sessions on past days
        if (!$sessionId && $startTime->lt(now()->startOfDay())) {
            Notification::make()
                ->danger()
                ->title('Invalid Date')
                ->body('Cannot schedule sessions on past days.')
                ->send();
            return;
        }

        $sessionData = [
            'service_id' => !empty($data['service_id']) ? (int) $data['service_id'] : null,
            'event_type' => !empty($data['event_type']) ? $data['event_type'] : 'live_in_person',
            'title' => trim($data['title']),
            'start_time' => $startTime,
            'end_time' => !empty($data['end_time']) ? Carbon::parse($data['end_time']) : $startTime->copy()->addHour(),
            'location_or_link' => !empty($data['location_or_link']) ? trim($data['location_or_link']) : null,
            'max_seats' => max(1, (int) ($data['max_seats'] ?? 10)),
            'drop_in_price' => (isset($data['drop_in_price']) && filled($data['drop_in_price'])) ? (float) $data['drop_in_price'] : null,
            'stripe_payment_link' => !empty($data['stripe_payment_link']) ? trim($data['stripe_payment_link']) : null,
            'is_recurring' => (bool) ($data['is_recurring'] ?? false),
            'recurrence_rule' => !empty($data['is_recurring']) ? ($data['recurrence_rule'] ?? 'every_4_weeks') : null,
            'recurrence_end_date' => (!empty($data['is_recurring']) && !empty($data['recurrence_end_date'])) ? Carbon::parse($data['recurrence_end_date']) : null,
        ];

        if ($sessionId) {
            $session = Session::findOrFail($sessionId);
            $session->update($sessionData);
            $session->plans()->sync($planIds);

            $recurringCount = 0;
            if ($session->is_recurring) {
                $recurringCount = $session->syncRecurringInstances();
            }

            Notification::make()
                ->success()
                ->title('Session Updated')
                ->body($recurringCount > 0
                    ? "Updated session and synced {$recurringCount} recurring occurrences in calendar."
                    : 'Session updated successfully.')
                ->send();
        } else {
            $session = Session::create($sessionData);
            if (!empty($planIds)) {
                $session->plans()->sync($planIds);
            }

            $recurringCount = 0;
            if ($session->is_recurring) {
                $recurringCount = $session->syncRecurringInstances();
            }

            Notification::make()
                ->success()
                ->title('Session Scheduled!')
                ->body($recurringCount > 0
                    ? "Scheduled session with {$recurringCount} recurring occurrences in the calendar."
                    : 'Session created successfully.')
                ->send();
        }

        $this->dispatch('calendar-refresh', events: $this->getEvents());
    }

    public function bookSession(int $sessionId): void
    {
        $session = Session::findOrFail($sessionId);

        if (($session->end_time ?? $session->start_time)?->isPast()) {
            Notification::make()
                ->warning()
                ->title('Session Has Passed')
                ->body('Reservations are closed for sessions that have already passed.')
                ->send();
            return;
        }

        if ($session->is_full) {
            Notification::make()
                ->danger()
                ->title('Session is Full')
                ->body('Sorry, all spots have been reserved for this session.')
                ->send();
            return;
        }

        Booking::firstOrCreate(
            ['user_id' => auth()->id(), 'session_id' => $session->id],
            ['status' => 'paid', 'amount_paid' => 0]
        );

        Notification::make()
            ->success()
            ->title('Spot Reserved!')
            ->body("You have reserved your spot for {$session->title}.")
            ->send();

        $this->dispatch('calendar-refresh', events: $this->getEvents());
    }

    public function cancelBooking(int $sessionId): void
    {
        $session = Session::findOrFail($sessionId);

        if (($session->end_time ?? $session->start_time)?->isPast()) {
            Notification::make()
                ->warning()
                ->title('Session Has Passed')
                ->body('You cannot cancel a reservation for a session that has already passed.')
                ->send();
            return;
        }

        Booking::where('user_id', auth()->id())
            ->where('session_id', $sessionId)
            ->delete();

        Notification::make()
            ->info()
            ->title('Reservation Cancelled')
            ->body('Your reservation has been cancelled.')
            ->send();

        $this->dispatch('calendar-refresh', events: $this->getEvents());
    }

    public function addAttendee(int $sessionId, int $userId): void
    {
        if (static::isSubscriber()) {
            abort(403);
        }

        $session = Session::findOrFail($sessionId);

        if ($session->is_full) {
            Notification::make()
                ->danger()
                ->title('Session is Full')
                ->body("Cannot add attendee. Maximum capacity of {$session->max_seats} seats has been reached.")
                ->send();
            return;
        }

        $user = User::findOrFail($userId);

        Booking::firstOrCreate(
            ['user_id' => $user->id, 'session_id' => $session->id],
            ['status' => 'paid', 'amount_paid' => $session->drop_in_price ?? 0]
        );

        Notification::make()
            ->success()
            ->title('Attendee Added')
            ->body("{$user->name} has been booked for this session.")
            ->send();

        $this->newAttendeeUserId = null;
        $this->dispatch('calendar-refresh', events: $this->getEvents());
    }

    public function removeAttendee(int $bookingId): void
    {
        if (static::isSubscriber()) {
            abort(403);
        }

        $booking = Booking::with('user')->findOrFail($bookingId);
        $name = $booking->user?->name ?? 'Attendee';
        $booking->delete();

        Notification::make()
            ->info()
            ->title('Attendee Removed')
            ->body("{$name} was removed from this session.")
            ->send();

        $this->dispatch('calendar-refresh', events: $this->getEvents());
    }

    public function deleteSingleSession(int $sessionId): void
    {
        if (static::isSubscriber()) {
            abort(403);
        }

        $session = Session::findOrFail($sessionId);
        $title = $session->title;
        $dateFormatted = $session->start_time?->format('D, M d, Y') ?? 'this date';

        // If this session is the parent of a recurring series, promote the next child to avoid cascade deletion
        if ($session->recurringInstances()->exists()) {
            $children = $session->recurringInstances()->orderBy('start_time')->get();
            if ($children->isNotEmpty()) {
                $newParent = $children->first();
                $newParent->update([
                    'parent_session_id' => null,
                    'is_recurring' => true,
                    'recurrence_rule' => $session->recurrence_rule,
                    'recurrence_end_date' => $session->recurrence_end_date,
                ]);

                // Re-point other children to the newly promoted parent
                $session->recurringInstances()
                    ->where('id', '!=', $newParent->id)
                    ->update(['parent_session_id' => $newParent->id]);
            }
        }

        $session->delete();

        Notification::make()
            ->success()
            ->title('Session Deleted')
            ->body("Deleted single session \"{$title}\" ({$dateFormatted}).")
            ->send();

        $this->dispatch('calendar-refresh', events: $this->getEvents());
    }

    public function deleteSeries(int $sessionId): void
    {
        if (static::isSubscriber()) {
            abort(403);
        }

        $session = Session::findOrFail($sessionId);
        $title = $session->title;

        // If this is a child instance, find its parent to delete the entire series
        $parent = $session->parent_session_id
            ? Session::find($session->parent_session_id)
            : $session;

        if ($parent) {
            $parent->delete();
        } else {
            $session->delete();
        }

        Notification::make()
            ->success()
            ->title('Series Deleted')
            ->body("Deleted all recurring sessions for \"{$title}\".")
            ->send();

        $this->dispatch('calendar-refresh', events: $this->getEvents());
    }

    public function deleteSession(int $sessionId): void
    {
        $this->deleteSingleSession($sessionId);
    }

    public function getViewData(): array
    {
        $countsByType = [
            'live_in_person' => Session::where('event_type', 'live_in_person')->count(),
            'live_online' => Session::where('event_type', 'live_online')->count(),
            'recorded' => Session::where('event_type', 'recorded')->count(),
        ];

        return [
            'totalSessionsCount' => Session::count(),
            'eventTypes' => [
                ['id' => 'live_in_person', 'label' => 'Live (In-person)', 'short_label' => 'In-Person', 'count' => $countsByType['live_in_person']],
                ['id' => 'live_online', 'label' => 'Live Online', 'short_label' => 'Live Online', 'count' => $countsByType['live_online']],
                ['id' => 'recorded', 'label' => 'Recorded', 'short_label' => 'Recorded', 'count' => $countsByType['recorded']],
            ],
            'plans' => Plan::where('is_active', true)->orderBy('sort_order')->get(),
            'users' => static::isSubscriber() ? collect() : User::orderBy('name')->get(),
            'initialEvents' => $this->getEvents(),
            'isAdmin' => !static::isSubscriber(),
        ];
    }
}
