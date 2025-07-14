<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function success()
    {
        return view('subscribe.success');
    }

    public function cancel(Request $request)
    {
        $error = $request->query('error');
        $message = $request->query('message', 'Your subscription process was cancelled. No charges were made.');

        return view('subscribe.cancel', compact('error', 'message'));
    }
}
