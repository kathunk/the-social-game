<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function success()
    {
        return view('subscribe.success');
    }

    public function cancel()
    {
        return view('subscribe.cancel');
    }
}
