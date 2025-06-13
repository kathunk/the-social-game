<?php

namespace App\Livewire;

use Livewire\Component;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Illuminate\Support\Facades\URL;

class Subscribe extends Component
{
    public function checkout()
    {
        try {
            $checkout = auth()->user()->newSubscription('default', config('services.stripe.price_id'))
                ->allowPromotionCodes()
                ->checkout([
                    'success_url' => route('subscribe.success'),
                    'cancel_url' => route('subscribe.cancel'),
                ]);

            return redirect($checkout->url);
        } catch (IncompletePayment $exception) {
            return redirect()->route('cashier.payment', [
                $exception->payment->id,
                'redirect' => URL::signedRoute('subscribe.success'),
            ]);
        }
    }

    public function render()
    {
        return view('livewire.subscribe');
    }
}
