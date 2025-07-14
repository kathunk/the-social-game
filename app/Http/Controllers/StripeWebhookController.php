<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    public function handleWebhook(Request $request): Response
    {
        $response = parent::handleWebhook($request);

        // Only log if there's an error (Stripe dashboard handles successful webhooks)
        if ($response->getStatusCode() !== 200) {
            Log::error('Stripe webhook failed', [
                'status' => $response->getStatusCode(),
                'payload' => json_decode($request->getContent(), true),
            ]);
        }

        return $response;
    }
}
