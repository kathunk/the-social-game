<?php

namespace App\Notifications\Channels;

use App\Services\TelegramBotService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class TelegramChannel
{
    public function __construct(
        private TelegramBotService $telegram
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toTelegram')) {
            return;
        }

        $chatId = $notifiable->telegram_chat_id;

        if (! $chatId) {

            return;
        }

        $message = $notification->toTelegram($notifiable);

        $success = $this->telegram->sendMessage($chatId, $message);

        if ($success) {
        } else {
            Log::error('Telegram notification failed', [
                'user_id' => $notifiable->id,
                'chat_id' => $chatId,
            ]);
        }
    }
}
