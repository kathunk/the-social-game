<?php

namespace App\Notifications\Concerns;

trait ChecksPlayerChannelPreferences
{
    // Per-game player channel overrides win when a player is attached;
    // otherwise fall back to the user's profile-level channel settings
    protected function wantsVia(object $notifiable, string $type): bool
    {
        return $this->player
            ? $this->player->wantsNotificationVia($type)
            : $notifiable->wantsNotificationVia($type);
    }
}
