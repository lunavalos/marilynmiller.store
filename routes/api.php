<?php

use App\Models\WebsiteContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\StripeWebhookController;
use App\Http\Controllers\Api\SubscriberController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/simulate-payment/{user}', [AuthController::class, 'simulatePayment']);
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn (Request $request) => $request->user()->load('plan'));
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::prefix('subscriber')->group(function () {
        Route::get('/dashboard', [SubscriberController::class, 'dashboard']);
        Route::get('/courses', [SubscriberController::class, 'courses']);
        Route::get('/courses/{id}', [SubscriberController::class, 'course']);
        Route::get('/videos', [SubscriberController::class, 'videos']);
        Route::get('/videos/{id}', [SubscriberController::class, 'video']);
        Route::get('/live-events', [SubscriberController::class, 'liveEvents']);
        Route::get('/sessions', [SubscriberController::class, 'sessions']);
        Route::post('/sessions/{sessionId}/book', [SubscriberController::class, 'bookSession']);
        Route::get('/bookings', [SubscriberController::class, 'myBookings']);
        Route::get('/profile', [SubscriberController::class, 'profile']);
        Route::patch('/profile', [SubscriberController::class, 'updateProfile']);
    });
});

Route::get('/content', function () {
    $content = WebsiteContent::first();
    $plans = \App\Models\Plan::where('is_active', true)->orderBy('sort_order')->get();
    
    if (!$content) {
        return response()->json([
            'gallery' => [],
            'plans' => $plans,
            'testimonials' => [],
            'faqs' => [],
        ]);
    }

    // Process image paths for full URLs
    $data = $content->toArray();
    $data['plans'] = $plans;
    
    if (!empty($data['gallery'])) {
        $data['gallery'] = array_map(fn($path) => asset("storage/$path"), $data['gallery']);
    }

    if (!empty($data['testimonials'])) {
        foreach ($data['testimonials'] as &$testimonial) {
            if (!empty($testimonial['image'])) {
                $testimonial['image'] = asset("storage/" . $testimonial['image']);
            }
        }
    }

    if (!empty($data['hero']['image'])) {
        $data['hero']['image'] = asset("storage/" . $data['hero']['image']);
    }

    return response()->json($data);
});
