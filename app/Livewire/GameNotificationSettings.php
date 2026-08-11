<?php

namespace App\Livewire;

use App\Events\PlayerUpdatedNotificationChannels;
use App\Models\Game;
use App\Models\Player;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Thunk\Verbs\Facades\Verbs;

class GameNotificationSettings extends Component
{
    public Game $game;

    public array $channels = [];

    public function mount(Game $game)
    {
        $this->game = $game;

        $player = $this->player;

        if (! $player) {
            return;
        }

        foreach ($this->configuredChannels as $key => $label) {
            $this->channels[$key] = $player->wantsNotificationVia($key);
        }
    }

    #[Computed]
    public function player(): ?Player
    {
        return auth()->user()
            ?->players()
            ->where('game_id', $this->game->id)
            ->first();
    }

    #[Computed]
    public function configuredChannels()
    {
        $user = auth()->user();

        return collect(Player::NOTIFICATION_CHANNELS)
            ->filter(fn ($label, $key) => match ($key) {
                'notify_via_email' => true,
                'notify_via_sms' => false, // SMS is not user-configurable yet
                'notify_via_discord' => ! empty($user->default_discord_webhook),
                'notify_via_telegram' => ! empty($user->telegram_chat_id),
                'notify_via_browser' => $user->hasPushSubscriptions(),
            });
    }

    public function updatedChannels()
    {
        PlayerUpdatedNotificationChannels::fire(
            player_id: $this->player->id,
            game_id: $this->game->id,
            notification_channels: array_map(
                fn ($enabled) => (bool) $enabled,
                $this->channels,
            ),
        );

        Verbs::commit();
    }

    public function render()
    {
        return view('livewire.game-notification-settings');
    }
}
