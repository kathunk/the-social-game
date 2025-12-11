<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

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
            Log::warning('SMS notification attempted but no phone number configured', [
                'user_id' => $notifiable->id,
            ]);

            return;
        }

        $message = $notification->toSms($notifiable);

        Log::info('SMS notification channel called', [
            'user_id' => $notifiable->id,
            'phone_number' => $phoneNumber,
            'message' => $message,
        ]);

        // TODO: Integrate with actual SMS service (Twilio, AWS SNS, etc.)
        // For now, we'll just log it
        Log::info('SMS notification would be sent (not implemented yet)', [
            'user_id' => $notifiable->id,
            'phone_number' => $phoneNumber,
            'message' => $message,
        ]);

        // Example integration with a service:
        // You would replace this with actual SMS service calls
        // For example, with Twilio:
        // Twilio::message($phoneNumber, $message);
    }
}
