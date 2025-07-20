<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Livewire\Component;
use Stripe\Exception\ApiErrorException;

class Subscribe extends Component
{
    public function checkout()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()
                ->route('login')
                ->with('error', 'You must be logged in to subscribe.');
        }

        try {
            $checkout = $user
                ->newSubscription('default', config('services.stripe.price_id'))
                ->allowPromotionCodes()
                ->checkout([
                    'success_url' => route('subscribe.success'),
                    'cancel_url' => route('subscribe.cancel'),
                ]);

            return redirect($checkout->url);
        } catch (IncompletePayment $exception) {
            // Stripe will handle incomplete payments
            return redirect()->route('cashier.payment', [
                $exception->payment->id,
                'redirect' => URL::signedRoute('subscribe.success'),
            ]);
        } catch (ApiErrorException $exception) {
            // Stripe's dashboard will have detailed logs for API errors
            return redirect()->route('subscribe.cancel', [
                'error' => 'payment_error',
                'message' => 'There was an error connecting to our payment processor. Please try again.',
            ]);
        } catch (\Exception $exception) {
            // Log application-specific errors that Stripe wouldn't know about
            Log::error('Unexpected error during checkout', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return redirect()->route('subscribe.cancel', [
                'error' => 'general_error',
                'message' => 'An unexpected error occurred. Please try again.',
            ]);
        }
    }

    public function render()
    {
        return view('livewire.subscribe');
    }
}
