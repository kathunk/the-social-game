<?php

namespace App\Models;

use App\Events\PlayerJoinedTeam;
use App\States\PlayerState;
use Glhd\Bits\Database\HasSnowflakes;
use Illuminate\Database\Eloquent\Model;
use Thunk\Verbs\Facades\Verbs;

class Player extends Model
{
    use HasSnowflakes;

    protected $guarded = [];

    protected $casts = [
        'notification_channels' => 'array',
    ];

    public const NOTIFICATION_CHANNELS = [
        'notify_via_email' => 'Email',
        'notify_via_sms' => 'Text message',
        'notify_via_discord' => 'Discord',
        'notify_via_telegram' => 'Telegram',
        'notify_via_browser' => 'Browser',
    ];

    public function state(): PlayerState
    {
        return PlayerState::load($this->id);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function joinTeam(Team $team)
    {
        PlayerJoinedTeam::fire(
            player_id: $this->id,
            team_id: $team->id,
            game_id: $this->game_id,
            previous_team_id: $this->team_id,
        );

        Verbs::commit();

        return $this->fresh();
    }

    public function wantsNotificationVia(string $type): bool
    {
        return ($this->notification_channels ?? [])[$type]
            ?? $this->user->wantsNotificationVia($type);
    }

    public function wantsGameNotifications(): bool
    {
        return collect(array_keys(self::NOTIFICATION_CHANNELS))
            ->contains(fn ($channel) => $this->wantsNotificationVia($channel));
    }

    public function updateModelWithStateData()
    {
        $this->update([
            'score' => $this->state()->score(),
            'hidden_score' => $this->state()->score(include_hidden: true),
        ]);
    }
}
