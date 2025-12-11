<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class WebPushService
{
    private WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('webpush.vapid.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ]);
    }

    public function send(array $subscription, array $payload): bool
    {
        try {
            $sub = Subscription::create($subscription);

            $result = $this->webPush->sendOneNotification(
                $sub,
                json_encode($payload)
            );

            return $result->isSuccess();
        } catch (\Exception $e) {
            Log::error('Web push send failed', [
                'error' => $e->getMessage(),
                'endpoint' => $subscription['endpoint'] ?? 'unknown',
            ]);

            return false;
        }
    }

    public function sendToMultiple(array $subscriptions, array $payload): array
    {
        $results = [];

        foreach ($subscriptions as $subscription) {
            $success = $this->send($subscription, $payload);
            $results[] = [
                'endpoint' => $subscription['endpoint'],
                'success' => $success,
            ];
        }

        return $results;
    }
}
