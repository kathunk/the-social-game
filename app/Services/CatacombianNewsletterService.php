<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CatacombianNewsletterService
{
    protected const ENDPOINT = 'https://catacombian.com/api/newsletter/subscribers';

    protected const SOURCE = 'the-social-game';

    public function __construct(
        protected ?string $token = null,
    ) {
        $this->token ??= config('services.catacombian.newsletter_token');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->token);
    }

    public function subscribe(string $email, ?string $name = null): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('CatacombianNewsletterService::subscribe called but token is not configured');

            return false;
        }

        $payload = [
            'email' => $email,
            'source' => self::SOURCE,
        ];

        if ($name) {
            $payload['name'] = $name;
        }

        try {
            $response = Http::withToken($this->token)
                ->acceptJson()
                ->asJson()
                ->timeout(5)
                ->post(self::ENDPOINT, $payload);
        } catch (ConnectionException $e) {
            Log::warning('CatacombianNewsletterService::subscribe connection failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        if ($response->successful()) {
            return true;
        }

        Log::warning('CatacombianNewsletterService::subscribe non-2xx response', [
            'email' => $email,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return false;
    }
}
