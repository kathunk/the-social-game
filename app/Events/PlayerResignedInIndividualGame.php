<?php

namespace App\Events;

use App\Events\Traits\HasActiveGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerResignedInIndividualGame extends Event
{
    use HasActiveGame, HasModifier, HasPlayer;

    public int $points;

    public int $hidden_points;

    public int $points_beneficiary_id;

    public int $hidden_points_beneficiary_id;

    public function validate()
    {
        $points_beneficiary = PlayerState::load($this->points_beneficiary_id);
        $hidden_points_beneficiary = PlayerState::load($this->hidden_points_beneficiary_id);

        $player = $this->state(PlayerState::class);

        $this->assert(
            $this->points_beneficiary_id !== $this->player_id,
            'You cannot resign to yourself',
        );

        $this->assert(
            $points_beneficiary->game_id === $this->game_id && $hidden_points_beneficiary->game_id === $this->game_id,
            'Beneficiary must be in the same game',
        );

        $this->assert(
            $points_beneficiary->status === 'active' && $hidden_points_beneficiary->status === 'active',
            'Beneficiary must be active',
        );

        $this->assert(
            $this->points === $player->score(),
            'Points do not match player score',
        );

        $this->assert(
            $this->hidden_points === $player->score(true) - $player->score(),
            'Hidden points do not match player hidden score',
        );
    }

    public function applyToGame(GameState $game)
    {
        $game->resigned_player_ids->push($this->player_id);
        $game->player_ids = $game->player_ids->reject(fn (int $player_id) => $player_id === $this->player_id);

        $points_beneficiary = PlayerState::load($this->points_beneficiary_id);
        $hidden_points_beneficiary = PlayerState::load($this->hidden_points_beneficiary_id);

        if ($this->points !== 0) {
            $points_beneficiary->addToScoreHistory(
                icon: '🎁',
                points: $this->points,
                description: 'Inherited points from '.$this->state(PlayerState::class)->name,
            );
        }

        if ($this->hidden_points !== 0) {
            $hidden_points_beneficiary->addToScoreHistory(
                icon: '🎁',
                points: $this->hidden_points,
                description: 'Inherited hidden points from '.$this->state(PlayerState::class)->name,
                is_hidden: true,
            );
        }
    }

    public function applyToPlayer(PlayerState $player)
    {
        $player->addToScoreHistory(
            icon: '👻',
            points: -$this->points,
            description: $this->state(PlayerState::class)->name.' resigned',
        );

        if ($this->hidden_points !== 0) {
            $player->addToScoreHistory(
                icon: '👻',
                points: -$this->hidden_points,
                description: $this->state(PlayerState::class)->name.' resigned',
                is_hidden: true,
            );
        }
    }

    public function handle()
    {
        $player = Player::find($this->player_id);
        $player->status = 'resigned';
        $player->save();
        $player->updateModelWithStateData();

        $points_beneficiary = Player::find($this->points_beneficiary_id);
        $hidden_points_beneficiary = Player::find($this->hidden_points_beneficiary_id);

        $points_beneficiary->updateModelWithStateData();
        $hidden_points_beneficiary->updateModelWithStateData();
    }
}
