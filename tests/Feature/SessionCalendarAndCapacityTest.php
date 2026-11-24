<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Plan;
use App\Models\Service;
use App\Models\Session;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionCalendarAndCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_plan_with_every_4_weeks_billing_cycle(): void
    {
        $plan = Plan::create([
            'name_en' => '4-Week Bootcamp Membership',
            'name_es' => 'Membresía Bootcamp 4 Semanas',
            'price' => 80.00,
            'billing_cycle' => 'every_4_weeks',
            'is_active' => true,
        ]);

        $this->assertEquals('every_4_weeks', $plan->billing_cycle);
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'billing_cycle' => 'every_4_weeks',
        ]);
    }

    public function test_session_recurrence_every_4_weeks_generates_exact_28_day_occurrences(): void
    {
        $service = Service::create([
            'name' => 'Gym Training',
            'type' => 'live_in_person',
            'description' => 'In-person gym sessions',
        ]);

        $start = now()->addDay()->setHour(10)->setMinute(0)->setSecond(0);

        $session = Session::create([
            'service_id' => $service->id,
            'title' => 'Bootcamp 4-Week Cycle',
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'max_seats' => 10,
            'drop_in_price' => 20.00,
            'stripe_payment_link' => 'https://buy.stripe.com/dropin_pass',
            'is_recurring' => true,
            'recurrence_rule' => 'every_4_weeks',
            'recurrence_end_date' => $start->copy()->addWeeks(12),
        ]);

        $instancesCount = $session->syncRecurringInstances();

        // Should generate 3 future occurrences: at +4 weeks, +8 weeks, +12 weeks
        $this->assertEquals(3, $instancesCount);
        $children = $session->recurringInstances()->orderBy('start_time')->get();
        $this->assertCount(3, $children);

        // Verify each instance is exactly 28 days (4 weeks) apart
        $this->assertEquals(
            $start->copy()->addWeeks(4)->toDateTimeString(),
            $children[0]->start_time->toDateTimeString()
        );
        $this->assertEquals(
            $start->copy()->addWeeks(8)->toDateTimeString(),
            $children[1]->start_time->toDateTimeString()
        );
        $this->assertEquals(
            $start->copy()->addWeeks(12)->toDateTimeString(),
            $children[2]->start_time->toDateTimeString()
        );

        // Verify each child inherited capacity, drop-in pricing, and payment links
        foreach ($children as $child) {
            $this->assertEquals(10, $child->max_seats);
            $this->assertEquals(20.00, (float) $child->drop_in_price);
            $this->assertEquals('https://buy.stripe.com/dropin_pass', $child->stripe_payment_link);
        }
    }

    public function test_seat_capacity_and_is_full_attributes(): void
    {
        $service = Service::create([
            'name' => 'Small Group Training',
            'type' => 'live_in_person',
        ]);

        $session = Session::create([
            'service_id' => $service->id,
            'title' => 'VIP 2-Seat Training',
            'start_time' => now()->addDay(),
            'end_time' => now()->addDay()->addHour(),
            'max_seats' => 2,
        ]);

        $this->assertEquals(2, $session->available_seats);
        $this->assertFalse($session->is_full);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Booking::create(['user_id' => $user1->id, 'session_id' => $session->id, 'status' => 'paid']);
        $session->refresh();

        $this->assertEquals(1, $session->available_seats);
        $this->assertFalse($session->is_full);

        Booking::create(['user_id' => $user2->id, 'session_id' => $session->id, 'status' => 'paid']);
        $session->refresh();

        $this->assertEquals(0, $session->available_seats);
        $this->assertTrue($session->is_full);
    }

    public function test_calendar_page_returns_correct_events_with_seats_and_drop_in(): void
    {
        $service = Service::create([
            'name' => 'Pilates Core',
            'type' => 'live_in_person',
        ]);

        $session = Session::create([
            'service_id' => $service->id,
            'title' => 'Pilates Saturday Class',
            'start_time' => now()->addDays(3),
            'end_time' => now()->addDays(3)->addHour(),
            'max_seats' => 8,
            'drop_in_price' => 30.00,
            'stripe_payment_link' => 'https://buy.stripe.com/pilates_dropin',
        ]);

        $calendarPage = new \App\Filament\Pages\SessionsCalendar();
        $events = $calendarPage->getEvents();

        $this->assertNotEmpty($events);
        $event = collect($events)->firstWhere('extendedProps.session_id', $session->id);

        $this->assertNotNull($event);
        $this->assertEquals('Pilates Saturday Class', $event['title']);
        $this->assertEquals(8, $event['extendedProps']['max_seats']);
        $this->assertEquals(8, $event['extendedProps']['available_seats']);
        $this->assertFalse($event['extendedProps']['is_full']);
        $this->assertEquals('30.00', $event['extendedProps']['drop_in_price']);
        $this->assertEquals('https://buy.stripe.com/pilates_dropin', $event['extendedProps']['stripe_payment_link']);
    }

    public function test_calendar_page_save_session_creates_and_edits_sessions(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $service = Service::create([
            'name' => 'CrossFit WOD',
            'type' => 'live_in_person',
        ]);

        $page = new \App\Filament\Pages\SessionsCalendar();

        // 1. Test Create via saveSession (simulate clicking on an empty slot)
        $start = now()->addDays(2)->setHour(9)->setMinute(0);
        $page->saveSession([
            'service_id' => $service->id,
            'title' => 'Morning WOD',
            'start_time' => $start->format('Y-m-d\TH:i'),
            'end_time' => $start->copy()->addHour()->format('Y-m-d\TH:i'),
            'max_seats' => 12,
            'drop_in_price' => 22.50,
            'stripe_payment_link' => 'https://buy.stripe.com/wod_pass',
            'is_recurring' => false,
        ]);

        $created = Session::where('title', 'Morning WOD')->first();
        $this->assertNotNull($created);
        $this->assertEquals(12, $created->max_seats);
        $this->assertEquals(22.50, (float) $created->drop_in_price);

        // 2. Test Edit via saveSession (simulate clicking on the session and saving changes)
        $page->saveSession([
            'id' => $created->id,
            'service_id' => $service->id,
            'title' => 'Morning WOD - Advanced',
            'start_time' => $created->start_time->format('Y-m-d\TH:i'),
            'end_time' => $created->end_time->format('Y-m-d\TH:i'),
            'max_seats' => 15,
            'drop_in_price' => 25.00,
            'stripe_payment_link' => 'https://buy.stripe.com/wod_advanced',
            'is_recurring' => false,
        ]);

        $updated = Session::find($created->id);
        $this->assertEquals('Morning WOD - Advanced', $updated->title);
        $this->assertEquals(15, $updated->max_seats);
        $this->assertEquals(25.00, (float) $updated->drop_in_price);
        $this->assertEquals('https://buy.stripe.com/wod_advanced', $updated->stripe_payment_link);
    }

    public function test_cannot_create_session_in_past_days(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $service = Service::create([
            'name' => 'Yoga Flow',
            'type' => 'live_in_person',
        ]);

        $page = new \App\Filament\Pages\SessionsCalendar();

        // Attempt to create session yesterday
        $pastDate = now()->subDays(2)->setHour(10)->setMinute(0);
        $page->saveSession([
            'service_id' => $service->id,
            'title' => 'Past Yoga Session',
            'start_time' => $pastDate->format('Y-m-d\TH:i'),
            'end_time' => $pastDate->copy()->addHour()->format('Y-m-d\TH:i'),
            'max_seats' => 10,
        ]);

        // Verify session was NOT created
        $this->assertDatabaseMissing('service_sessions', [
            'title' => 'Past Yoga Session',
        ]);
    }

    public function test_delete_single_session_removes_only_one_day_from_recurring_series_without_affecting_others(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $service = Service::create([
            'name' => 'Strength Class',
            'type' => 'live_in_person',
        ]);

        $start = now()->addDays(2)->setHour(10)->setMinute(0)->setSecond(0);

        // Create parent recurring session with 3 occurrences (+4, +8, +12 weeks)
        $parent = Session::create([
            'service_id' => $service->id,
            'title' => 'Weekly Strength',
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'max_seats' => 10,
            'is_recurring' => true,
            'recurrence_rule' => 'every_4_weeks',
            'recurrence_end_date' => $start->copy()->addWeeks(12),
        ]);
        $parent->syncRecurringInstances();

        // Total 4 sessions: parent + 3 children
        $this->assertEquals(4, Session::count());

        $page = new \App\Filament\Pages\SessionsCalendar();

        // 1. Delete parent session using deleteSingleSession
        $page->deleteSingleSession($parent->id);

        // Parent should be deleted, but the remaining 3 instances MUST remain in the database!
        $this->assertDatabaseMissing('service_sessions', ['id' => $parent->id]);
        $this->assertEquals(3, Session::count());

        // One of the remaining instances was promoted to new parent
        $newParent = Session::whereNull('parent_session_id')->first();
        $this->assertNotNull($newParent);
        $this->assertTrue($newParent->is_recurring);
        $this->assertEquals('every_4_weeks', $newParent->recurrence_rule);

        // 2. Now delete one child instance using deleteSingleSession
        $child = Session::whereNotNull('parent_session_id')->first();
        $page->deleteSingleSession($child->id);

        $this->assertDatabaseMissing('service_sessions', ['id' => $child->id]);
        $this->assertEquals(2, Session::count());
        $this->assertDatabaseHas('service_sessions', ['id' => $newParent->id]);
    }

    public function test_delete_series_removes_all_recurring_occurrences(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $service = Service::create([
            'name' => 'Cardio Blast',
            'type' => 'live_in_person',
        ]);

        $start = now()->addDays(5)->setHour(8)->setMinute(0)->setSecond(0);

        $parent = Session::create([
            'service_id' => $service->id,
            'title' => 'Cardio 4-Week Series',
            'start_time' => $start,
            'end_time' => $start->copy()->addHour(),
            'max_seats' => 10,
            'is_recurring' => true,
            'recurrence_rule' => 'every_4_weeks',
            'recurrence_end_date' => $start->copy()->addWeeks(8),
        ]);
        $parent->syncRecurringInstances();

        // 1 parent + 2 children = 3 total
        $this->assertEquals(3, Session::count());

        $page = new \App\Filament\Pages\SessionsCalendar();

        // Calling deleteSeries from one of the children should delete the entire series!
        $child = Session::whereNotNull('parent_session_id')->first();
        $page->deleteSeries($child->id);

        $this->assertEquals(0, Session::count());
    }

    public function test_can_create_and_filter_sessions_by_event_type_without_service(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($admin);

        $page = new \App\Filament\Pages\SessionsCalendar();

        $start = now()->addDays(2)->setHour(11)->setMinute(0);
        $page->saveSession([
            'title' => 'Live Zoom Yoga',
            'event_type' => 'live_online',
            'start_time' => $start->format('Y-m-d\TH:i'),
            'end_time' => $start->copy()->addHour()->format('Y-m-d\TH:i'),
            'max_seats' => 20,
        ]);

        $start2 = now()->addDays(3)->setHour(14)->setMinute(0);
        $page->saveSession([
            'title' => 'In-Person Studio Pilates',
            'event_type' => 'live_in_person',
            'start_time' => $start2->format('Y-m-d\TH:i'),
            'end_time' => $start2->copy()->addHour()->format('Y-m-d\TH:i'),
            'max_seats' => 10,
        ]);

        $this->assertDatabaseHas('service_sessions', [
            'title' => 'Live Zoom Yoga',
            'event_type' => 'live_online',
            'service_id' => null,
        ]);

        // Test filtering by event_type
        $page->filterByEventType('live_online');
        $events = $page->getEvents();
        $this->assertCount(1, $events);
        $this->assertEquals('Live Zoom Yoga', $events[0]['title']);

        $page->filterByEventType('live_in_person');
        $events = $page->getEvents();
        $this->assertCount(1, $events);
        $this->assertEquals('In-Person Studio Pilates', $events[0]['title']);

        $page->filterByEventType(null);
        $events = $page->getEvents();
        $this->assertCount(2, $events);
    }

    public function test_services_and_bookings_are_hidden_from_navigation(): void
    {
        $this->assertFalse(\App\Filament\Resources\ServiceResource::shouldRegisterNavigation());
        $this->assertFalse(\App\Filament\Resources\BookingResource::shouldRegisterNavigation());
    }
}
