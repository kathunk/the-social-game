<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    private string $botToken;

    private string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    public function sendMessage(string $chatId, string $text, array $options = []): bool
    {
        try {
            $response = Http::post("{$this->apiUrl}/sendMessage", array_merge([
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => 'HTML',
            ], $options));

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Telegram send message failed', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function setWebhook(string $url): bool
    {
        $response = Http::post("{$this->apiUrl}/setWebhook", [
            'url' => $url,
            'secret_token' => config('services.telegram.webhook_secret'),
        ]);

        return $response->successful();
    }
}
