<?php

namespace App\Notifications\Channels;

use App\Services\WebPushService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class WebPushChannel
{
    public function __construct(
        private WebPushService $webPush
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toWebPush')) {
            return;
        }

        $subscriptions = $notifiable->push_subscriptions ?? [];

        if (empty($subscriptions)) {

            return;
        }

        $payload = $notification->toWebPush($notifiable);

        $results = $this->webPush->sendToMultiple($subscriptions, $payload);

        // Remove failed subscriptions
        $validSubscriptions = [];
        foreach ($results as $i => $result) {
            if ($result['success']) {
                $validSubscriptions[] = $subscriptions[$i];
            } else {
                Log::warning('Removing failed push subscription', [
                    'user_id' => $notifiable->id,
                    'endpoint' => $result['endpoint'],
                ]);
            }
        }

        // Update user's subscriptions if any were removed
        if (count($validSubscriptions) !== count($subscriptions)) {
            $notifiable->push_subscriptions = $validSubscriptions;
            $notifiable->save();
        }

    }
}
