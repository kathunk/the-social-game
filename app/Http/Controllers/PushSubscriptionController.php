<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $subscription = [
            'endpoint' => $validated['endpoint'],
            'keys' => $validated['keys'],
        ];

        \App\Events\UserSubscribedToPush::fire(
            user_id: Auth::id(),
            subscription: $subscription
        );

        return response()->json(['success' => true]);
    }

    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        \App\Events\UserUnsubscribedFromPush::fire(
            user_id: Auth::id(),
            endpoint: $validated['endpoint']
        );

        return response()->json(['success' => true]);
    }
}
