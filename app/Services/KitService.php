<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around the Kit (formerly ConvertKit) v4 API.
 *
 * @see https://developers.kit.com/v4
 */
class KitService
{
    protected const BASE_URL = 'https://api.kit.com/v4';

    public function __construct(
        protected ?string $apiKey = null,
        protected ?string $formId = null,
    ) {
        $this->apiKey ??= config('services.kit.api_key');
        $this->formId ??= config('services.kit.form_id');
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->formId);
    }

    /**
     * Add a subscriber to the configured Kit form.
     * Returns true on success, false on failure.
     */
    public function subscribe(string $email, ?string $firstName = null): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('KitService::subscribe called but Kit is not configured');

            return false;
        }

        $payload = ['email_address' => $email];

        if ($firstName) {
            $payload['first_name'] = $firstName;
        }

        $response = Http::withHeaders([
            'X-Kit-Api-Key' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post(self::BASE_URL."/forms/{$this->formId}/subscribers", $payload);

        if ($response->failed()) {
            Log::warning('KitService::subscribe failed', [
                'email' => $email,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Unsubscribe a subscriber by email.
     * Kit's v4 API requires looking up the subscriber id first, then
     * calling the unsubscribe endpoint on that id.
     */
    public function unsubscribe(string $email): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('KitService::unsubscribe called but Kit is not configured');

            return false;
        }

        $lookup = Http::withHeaders([
            'X-Kit-Api-Key' => $this->apiKey,
            'Accept' => 'application/json',
        ])->get(self::BASE_URL.'/subscribers', ['email_address' => $email]);

        if ($lookup->failed()) {
            Log::warning('KitService::unsubscribe lookup failed', [
                'email' => $email,
                'status' => $lookup->status(),
                'body' => $lookup->body(),
            ]);

            return false;
        }

        $subscriberId = $lookup->json('subscribers.0.id');

        if (! $subscriberId) {
            // Subscriber not found - treat as success (already unsubscribed)
            return true;
        }

        $response = Http::withHeaders([
            'X-Kit-Api-Key' => $this->apiKey,
            'Accept' => 'application/json',
        ])->post(self::BASE_URL."/subscribers/{$subscriberId}/unsubscribe");

        if ($response->failed()) {
            Log::warning('KitService::unsubscribe failed', [
                'email' => $email,
                'subscriber_id' => $subscriberId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }
}
