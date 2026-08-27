<?php

namespace App\Events;

use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerUpdatedNotificationChannels extends Event
{
    use HasGame, HasPlayer;

    public array $notification_channels;

    public function validate()
    {
        $this->validatePlayer();
    }

    public function applyToPlayer(PlayerState $player)
    {
        $player->notification_channels = $this->notification_channels;
    }

    public function handle()
    {
        $this->player()->update([
            'notification_channels' => $this->notification_channels,
        ]);
    }
}
