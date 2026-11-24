<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Session extends Model
{
    protected $table = 'service_sessions';

    protected $fillable = [
        'parent_session_id',
        'service_id',
        'event_type',
        'title',
        'start_time',
        'end_time',
        'is_recurring',
        'recurrence_rule',
        'recurrence_end_date',
        'location_or_link',
        'max_seats',
        'drop_in_price',
        'stripe_payment_link',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'recurrence_end_date' => 'date',
        'is_recurring' => 'boolean',
        'max_seats' => 'integer',
        'drop_in_price' => 'decimal:2',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Session::class, 'parent_session_id');
    }

    public function recurringInstances(): HasMany
    {
        return $this->hasMany(Session::class, 'parent_session_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(Plan::class, 'plan_service_session');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function getBookedSeatsCountAttribute(): int
    {
        return $this->bookings()
            ->whereIn('status', ['paid', 'attended', 'confirmed'])
            ->count();
    }

    public function getAvailableSeatsAttribute(): int
    {
        if ($this->max_seats <= 0) {
            return 999;
        }

        return max(0, $this->max_seats - $this->booked_seats_count);
    }

    public function getIsFullAttribute(): bool
    {
        return ($this->max_seats > 0) && ($this->available_seats <= 0);
    }

    /**
     * Generate recurring session occurrences based on recurrence_rule and recurrence_end_date.
     */
    public function syncRecurringInstances(): int
    {
        if (!$this->is_recurring || !$this->recurrence_rule || !$this->start_time) {
            return 0;
        }

        $durationMinutes = $this->end_time ? $this->start_time->diffInMinutes($this->end_time) : 60;
        $endDate = $this->recurrence_end_date
            ? Carbon::parse($this->recurrence_end_date)->endOfDay()
            : $this->start_time->copy()->addMonths(3)->endOfDay();

        $currentStart = $this->start_time->copy();
        $planIds = $this->plans()->pluck('plans.id')->toArray();
        $count = 0;

        // Existing child instances keyed by their start_time string
        $existingChildren = $this->recurringInstances()->get()->keyBy(fn ($item) => $item->start_time?->format('Y-m-d H:i'));
        $keptChildIds = [];

        while (true) {
            switch ($this->recurrence_rule) {
                case 'daily':
                    $currentStart->addDay();
                    break;
                case 'weekly':
                    $currentStart->addWeek(); // +7 days
                    break;
                case 'biweekly':
                    $currentStart->addWeeks(2); // +14 days
                    break;
                case 'every_4_weeks':
                    $currentStart->addWeeks(4); // +28 days (every 4 weeks)
                    break;
                case 'monthly':
                    $currentStart->addMonth();
                    break;
                default:
                    return $count;
            }

            if ($currentStart->gt($endDate)) {
                break;
            }

            $currentEnd = $currentStart->copy()->addMinutes($durationMinutes);
            $key = $currentStart->format('Y-m-d H:i');

            if ($existingChildren->has($key)) {
                $child = $existingChildren->get($key);
                $child->update([
                    'service_id' => $this->service_id,
                    'event_type' => $this->event_type ?: 'live_in_person',
                    'title' => $this->title,
                    'end_time' => $currentEnd,
                    'location_or_link' => $this->location_or_link,
                    'max_seats' => $this->max_seats,
                    'drop_in_price' => $this->drop_in_price,
                    'stripe_payment_link' => $this->stripe_payment_link,
                ]);
            } else {
                $child = static::create([
                    'parent_session_id' => $this->id,
                    'service_id' => $this->service_id,
                    'event_type' => $this->event_type ?: 'live_in_person',
                    'title' => $this->title,
                    'start_time' => $currentStart->copy(),
                    'end_time' => $currentEnd,
                    'is_recurring' => false,
                    'location_or_link' => $this->location_or_link,
                    'max_seats' => $this->max_seats,
                    'drop_in_price' => $this->drop_in_price,
                    'stripe_payment_link' => $this->stripe_payment_link,
                ]);
            }

            if (!empty($planIds)) {
                $child->plans()->sync($planIds);
            }

            $keptChildIds[] = $child->id;
            $count++;

            // Safety limit (maximum 52 occurrences)
            if ($count >= 52) {
                break;
            }
        }

        // Delete any past generated child instances that no longer match recurrence AND have no bookings
        $this->recurringInstances()
            ->whereNotIn('id', $keptChildIds)
            ->whereDoesntHave('bookings')
            ->delete();

        return $count;
    }

    public function getEventTypeLabelAttribute(): string
    {
        return match ($this->event_type) {
            'live_in_person' => 'Live (In-person)',
            'live_online' => 'Live Online',
            'recorded' => 'Recorded',
            default => 'Live (In-person)',
        };
    }
}
