<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Vonage\Client;
use Vonage\Client\Credentials\Basic;
use Vonage\SMS\Message\SMS;

class SmsChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $phoneNumber = $notifiable->phone_number;

        if (! $phoneNumber) {

            return;
        }

        $message = $notification->toSms($notifiable);

        try {

            $client = new Client(
                new Basic(
                    config('services.vonage.key'),
                    config('services.vonage.secret')
                )
            );

            $response = $client->sms()->send(
                new SMS(
                    $phoneNumber,
                    config('services.vonage.sms_from'),
                    $message
                )
            );

            $responseMessage = $response->current();

            if ($responseMessage->getStatus() == 0) {
            } else {
                Log::error('SMS notification failed', [
                    'user_id' => $notifiable->id,
                    'status' => $responseMessage->getStatus(),
                    'error' => $responseMessage->getStatus(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('SMS notification exception', [
                'user_id' => $notifiable->id,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
