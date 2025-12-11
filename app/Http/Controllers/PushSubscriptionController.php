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

        $user = Auth::user();

        $subscription = [
            'endpoint' => $validated['endpoint'],
            'keys' => $validated['keys'],
        ];

        $user->addPushSubscription($subscription);

        return response()->json(['success' => true]);
    }

    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        Auth::user()->removePushSubscription($validated['endpoint']);

        return response()->json(['success' => true]);
    }
}
