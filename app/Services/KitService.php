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
     * Add a subscriber to the configured Kit form. This is a two-step flow:
     * 1. Create (or look up) the subscriber via POST /v4/subscribers - returns
     *    the subscriber id. This call is idempotent: re-posting the same email
     *    returns 200 with the existing subscriber.
     * 2. Add the subscriber to the form via
     *    POST /v4/forms/{form_id}/subscribers/{subscriber_id}
     *
     * Returns true on success, false on any failure.
     */
    public function subscribe(string $email, ?string $firstName = null): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('KitService::subscribe called but Kit is not configured');

            return false;
        }

        // Step 1: create / upsert the subscriber
        $createPayload = ['email_address' => $email];
        if ($firstName) {
            $createPayload['first_name'] = $firstName;
        }

        $createResponse = $this->client()->post(self::BASE_URL.'/subscribers', $createPayload);

        if ($createResponse->failed()) {
            Log::warning('KitService::subscribe create failed', [
                'email' => $email,
                'status' => $createResponse->status(),
                'body' => $createResponse->body(),
            ]);

            return false;
        }

        $subscriberId = $createResponse->json('subscriber.id');

        if (! $subscriberId) {
            Log::warning('KitService::subscribe got no subscriber id back', [
                'email' => $email,
                'body' => $createResponse->body(),
            ]);

            return false;
        }

        // Step 2: add the subscriber to the form
        $addResponse = $this->client()->post(
            self::BASE_URL."/forms/{$this->formId}/subscribers/{$subscriberId}"
        );

        if ($addResponse->failed()) {
            Log::warning('KitService::subscribe add-to-form failed', [
                'email' => $email,
                'subscriber_id' => $subscriberId,
                'form_id' => $this->formId,
                'status' => $addResponse->status(),
                'body' => $addResponse->body(),
            ]);

            return false;
        }

        return true;
    }

    protected function client()
    {
        return Http::withHeaders([
            'X-Kit-Api-Key' => $this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Unsubscribe a subscriber by email.
     * Looks up the subscriber id by email, then calls the unsubscribe endpoint.
     */
    public function unsubscribe(string $email): bool
    {
        if (! $this->isConfigured()) {
            Log::warning('KitService::unsubscribe called but Kit is not configured');

            return false;
        }

        $lookup = $this->client()->get(self::BASE_URL.'/subscribers', ['email_address' => $email]);

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

        $response = $this->client()->post(self::BASE_URL."/subscribers/{$subscriberId}/unsubscribe");

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
