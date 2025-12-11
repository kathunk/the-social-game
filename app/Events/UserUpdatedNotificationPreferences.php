<?php

namespace App\Events;

use App\Events\Traits\HasUser;
use Thunk\Verbs\Event;

class UserUpdatedNotificationPreferences extends Event
{
    use HasUser;

    public ?string $phone_number;

    public ?string $default_discord_webhook;

    public array $notification_preferences;

    public function handle()
    {
        $this->user()->update([
            'phone_number' => $this->phone_number,
            'default_discord_webhook' => $this->default_discord_webhook,
            'notification_preferences' => $this->notification_preferences,
        ]);
    }
}
