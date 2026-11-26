<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\WelcomeUserNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'plan_id' => 'required|exists:plans,id',
            'preferred_language' => 'nullable|string|in:en,es',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $plan = Plan::findOrFail($request->plan_id);
        $preferredLanguage = $request->input('preferred_language', 'en');

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'preferred_language' => $preferredLanguage,
            'password' => Hash::make($request->password),
            'plan_id' => $plan->id,
            'is_active' => false,
        ]);

        // Assign 'subscribed' role (Spatie Permission)
        $user->assignRole('subscribed');

        try {
            $user->notify(new WelcomeUserNotification(
                plainPassword: $request->password,
                panel: 'member',
                language: $preferredLanguage,
            ));
        } catch (Throwable $e) {
            Log::error('Failed to send welcome email on API registration', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $e->getMessage(),
            ]);
        }

        if (! $plan->stripe_payment_link) {
            // Simulate payment for development
            $checkoutUrl = url('/api/simulate-payment/'.$user->id);

            return response()->json([
                'checkout_url' => $checkoutUrl,
                'user' => $user,
            ]);
        }

        // Append client_reference_id to Stripe Link
        $paymentLink = $plan->stripe_payment_link;
        $separator = parse_url($paymentLink, PHP_URL_QUERY) ? '&' : '?';
        $checkoutUrl = $paymentLink.$separator.'client_reference_id='.$user->id;

        // Also we can pre-fill email using prefilled_email parameter
        $checkoutUrl .= '&prefilled_email='.urlencode($user->email);

        return response()->json([
            'checkout_url' => $checkoutUrl,
            'user' => $user,
        ]);
    }

    public function simulatePayment($userId)
    {
        $user = User::find($userId);
        if ($user) {
            $user->is_active = true;
            $user->save();
            $loginUrl = url('/member/login');

            return response("<h1>Simulation Success!</h1><p>User {$user->email} has been activated.</p><a href='{$loginUrl}'>Log in to Member Portal</a>", 200)
                ->header('Content-Type', 'text/html');
        }

        return response('User not found', 404);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['error' => 'Your account is not active. Please complete your payment.'], 403);
        }

        $token = $user->createToken('subscriber-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->load('plan'),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}
