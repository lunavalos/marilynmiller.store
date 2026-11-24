<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;
use App\Models\User;

class StripeWebhookController extends Controller
{
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        
        Log::info('Stripe Webhook Received: ' . ($payload['type'] ?? 'unknown'));

        if (isset($payload['type']) && $payload['type'] === 'checkout.session.completed') {
            $session = $payload['data']['object'];
            
            // Client reference ID is our user_id
            $userId = $session['client_reference_id'] ?? null;
            
            if ($userId) {
                $user = User::find($userId);
                
                if ($user) {
                    $user->is_active = true;
                    $user->save();
                    
                    Log::info("User {$userId} activated successfully via Stripe webhook.");
                } else {
                    Log::error("Webhook received for unknown user ID: {$userId}");
                }
            } else {
                Log::error('Webhook received without client_reference_id.');
            }
        }

        return response()->json(['status' => 'success']);
    }
}
