<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Course;
use App\Models\LiveEvent;
use App\Models\Session;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class SubscriberController extends Controller
{
    /**
     * Dashboard overview: user info, plan, content counts, upcoming events.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user()->load('plan');
        $planId = $user->plan_id;

        return response()->json([
            'user' => $user,
            'counts' => [
                'courses' => $planId ? Course::whereHas('plans', fn($q) => $q->where('plans.id', $planId))->where('is_published', true)->count() : 0,
                'videos' => $planId ? Video::whereHas('plans', fn($q) => $q->where('plans.id', $planId))->where('is_published', true)->count() : 0,
                'live_events' => $planId ? LiveEvent::whereHas('plans', fn($q) => $q->where('plans.id', $planId))->where('is_published', true)->count() : 0,
                'bookings' => $user->bookings()->count(),
            ],
            'upcoming_events' => $planId ? LiveEvent::whereHas('plans', fn($q) => $q->where('plans.id', $planId))
                ->where('is_published', true)
                ->where('start_time', '>=', now())
                ->orderBy('start_time')
                ->take(3)
                ->get() : [],
            'upcoming_sessions' => $planId ? Session::whereHas('plans', fn($q) => $q->where('plans.id', $planId))
                ->where('start_time', '>=', now())
                ->with('service')
                ->orderBy('start_time')
                ->take(5)
                ->get() : [],
        ]);
    }

    /**
     * List courses available for the user's plan.
     */
    public function courses(Request $request)
    {
        $planId = $request->user()->plan_id;
        if (!$planId) {
            return response()->json(['data' => []]);
        }

        $courses = Course::whereHas('plans', fn($q) => $q->where('plans.id', $planId))
            ->where('is_published', true)
            ->withCount('modules')
            ->get()
            ->map(function ($course) {
                if ($course->thumbnail_url) {
                    $course->thumbnail_url = asset('storage/' . $course->thumbnail_url);
                }
                return $course;
            });

        return response()->json(['data' => $courses]);
    }

    /**
     * Single course with modules and lessons.
     */
    public function course(Request $request, $id)
    {
        $planId = $request->user()->plan_id;

        $course = Course::whereHas('plans', fn($q) => $q->where('plans.id', $planId))
            ->where('is_published', true)
            ->with(['modules.lessons'])
            ->find($id);

        if (!$course) {
            return response()->json(['error' => 'Course not found or not available in your plan.'], 404);
        }

        if ($course->thumbnail_url) {
            $course->thumbnail_url = asset('storage/' . $course->thumbnail_url);
        }

        return response()->json(['data' => $course]);
    }

    /**
     * List videos available for the user's plan.
     */
    public function videos(Request $request)
    {
        $planId = $request->user()->plan_id;
        if (!$planId) {
            return response()->json(['data' => []]);
        }

        $videos = Video::whereHas('plans', fn($q) => $q->where('plans.id', $planId))
            ->where('is_published', true)
            ->get()
            ->map(function ($video) {
                if ($video->thumbnail_url) {
                    $video->thumbnail_url = asset('storage/' . $video->thumbnail_url);
                }
                return $video;
            });

        return response()->json(['data' => $videos]);
    }

    /**
     * Single video detail.
     */
    public function video(Request $request, $id)
    {
        $planId = $request->user()->plan_id;

        $video = Video::whereHas('plans', fn($q) => $q->where('plans.id', $planId))
            ->where('is_published', true)
            ->find($id);

        if (!$video) {
            return response()->json(['error' => 'Video not found or not available in your plan.'], 404);
        }

        if ($video->thumbnail_url) {
            $video->thumbnail_url = asset('storage/' . $video->thumbnail_url);
        }

        return response()->json(['data' => $video]);
    }

    /**
     * List live events available for the user's plan.
     */
    public function liveEvents(Request $request)
    {
        $planId = $request->user()->plan_id;
        if (!$planId) {
            return response()->json(['data' => []]);
        }

        $events = LiveEvent::whereHas('plans', fn($q) => $q->where('plans.id', $planId))
            ->where('is_published', true)
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function ($event) {
                if ($event->thumbnail_url) {
                    $event->thumbnail_url = asset('storage/' . $event->thumbnail_url);
                }
                return $event;
            });

        return response()->json(['data' => $events]);
    }

    /**
     * List sessions available for the user's plan.
     */
    public function sessions(Request $request)
    {
        $planId = $request->user()->plan_id;
        if (!$planId) {
            return response()->json(['data' => []]);
        }

        $sessions = Session::whereHas('plans', fn($q) => $q->where('plans.id', $planId))
            ->with('service')
            ->orderBy('start_time')
            ->get();

        return response()->json(['data' => $sessions]);
    }

    /**
     * Book a session.
     */
    public function bookSession(Request $request, $sessionId)
    {
        $planId = $request->user()->plan_id;

        $session = Session::whereHas('plans', fn($q) => $q->where('plans.id', $planId))->find($sessionId);

        if (!$session) {
            return response()->json(['error' => 'Session not found or not available in your plan.'], 404);
        }

        if (($session->end_time ?? $session->start_time)?->isPast()) {
            return response()->json(['error' => 'This session has already passed.'], 422);
        }

        // Check if session is full
        if ($session->is_full) {
            return response()->json(['error' => 'This session is full. No seats remaining.'], 422);
        }

        // Check if already booked
        $existing = Booking::where('user_id', $request->user()->id)
            ->where('session_id', $sessionId)
            ->first();

        if ($existing) {
            return response()->json(['error' => 'You have already booked this session.'], 409);
        }

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'session_id' => $sessionId,
            'status' => 'confirmed',
            'amount_paid' => 0,
        ]);

        return response()->json(['data' => $booking->load('session.service')], 201);
    }

    /**
     * List user's bookings.
     */
    public function myBookings(Request $request)
    {
        $bookings = Booking::where('user_id', $request->user()->id)
            ->with('session.service')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $bookings]);
    }

    /**
     * Get user profile.
     */
    public function profile(Request $request)
    {
        return response()->json(['data' => $request->user()->load('plan')]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json(['data' => $user->load('plan'), 'message' => 'Profile updated successfully.']);
    }
}
