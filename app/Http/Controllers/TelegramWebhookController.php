<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    public function __construct(
        private TelegramBotService $telegram
    ) {}

    public function handle(Request $request)
    {
        // Verify webhook secret
        $secret = $request->header('X-Telegram-Bot-Api-Secret-Token');
        if ($secret !== config('services.telegram.webhook_secret')) {
            return response('Unauthorized', 401);
        }

        $update = $request->all();

        if (! isset($update['message'])) {
            return response('OK');
        }

        $message = $update['message'];
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $username = $message['from']['username'] ?? null;

        Log::info('Telegram webhook received', [
            'chat_id' => $chatId,
            'text' => $text,
            'username' => $username,
        ]);

        // Handle /start command with verification token
        if (str_starts_with($text, '/start ')) {
            $token = trim(str_replace('/start ', '', $text));
            $this->handleVerification($chatId, $token, $username);
        }
        // Handle plain /start
        elseif ($text === '/start') {
            $this->telegram->sendMessage(
                $chatId,
                '👋 Welcome! To connect your account, please use the link from your profile settings.'
            );
        }

        return response('OK');
    }

    private function handleVerification(string $chatId, string $token, ?string $username): void
    {
        $user = User::where('telegram_verification_token', $token)->first();

        if (! $user) {
            $this->telegram->sendMessage(
                $chatId,
                '❌ Invalid verification code. Please generate a new one from your profile settings.'
            );

            return;
        }

        // Connect the account
        $user->telegram_chat_id = $chatId;
        $user->telegram_username = $username;
        $user->telegram_verification_token = null;
        $user->telegram_connected_at = now();
        $user->save();

        $this->telegram->sendMessage(
            $chatId,
            "✅ Your Telegram account has been successfully connected! You'll now receive game notifications here."
        );

        Log::info('Telegram account connected', [
            'user_id' => $user->id,
            'chat_id' => $chatId,
            'username' => $username,
        ]);
    }
}
