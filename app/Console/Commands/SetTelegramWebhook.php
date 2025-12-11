<?php

namespace App\Console\Commands;

use App\Services\TelegramBotService;
use Illuminate\Console\Command;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook';

    protected $description = 'Set the Telegram bot webhook URL';

    public function handle(TelegramBotService $telegram): int
    {
        $url = route('telegram.webhook');

        $this->info("Setting webhook to: {$url}");

        $success = $telegram->setWebhook($url);

        if ($success) {
            $this->info('✓ Webhook set successfully!');

            return Command::SUCCESS;
        }

        $this->error('✗ Failed to set webhook');

        return Command::FAILURE;
    }
}
