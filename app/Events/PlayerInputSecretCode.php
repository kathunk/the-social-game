<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\GameState;
use App\States\TeamState;
use App\States\PlayerState;
use App\States\ModifierState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasModifier;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;

class PlayerInputSecretCode extends Event
{
    use HasPlayer, HasGame, HasModifier;

    #[StateId(TeamState::class)]
    public ?int $team_id;

    public string $code;

    public bool $points_are_hidden;

    public int $point_reward;

    public string $game_type;

    public function validate()
    {
        if ($this->game_type === 'team') {
            $this->assert(
                $this->state(GameState::class)->team_ids->contains($this->team_id),
                'Team is not in the game'
            );

            $this->assert(
                $this->state(TeamState::class)->game_id === $this->game_id,
                'Team is not in the game'
            );
        }

        $this->assert(
            ! in_array($this->player_id, $this->state(ModifierState::class)->modifier_data['banned_player_ids']),
            'You are banned from submitting codes'
        );
    }

    public function applyToModifier(ModifierState $modifier)
    {
        $unused_codes = $modifier->modifier_data['unused_codes'];

        if (in_array($this->code, $unused_codes)) {
            $unused_codes = array_diff($unused_codes, [$this->code]);
            $used_codes = array_merge($modifier->modifier_data['used_codes'], [$this->code]);

            $modifier->modifier_data['unused_codes'] = $unused_codes;
            $modifier->modifier_data['used_codes'] = $used_codes;

            $point_recipient = $this->game_type === 'team' ? $this->state(TeamState::class) : $this->state(PlayerState::class);

            $point_recipient->addToScoreHistory(
                $this->point_reward,
                $this->state(PlayerState::class)->name . ' found a secret code',
                $this->points_are_hidden,
            );
        } else {
            $modifier->modifier_data['banned_player_ids'] = array_merge($modifier->modifier_data['banned_player_ids'], [$this->player_id]);
        }
    }

    public function handle()
    {
        // Most of yall don't get the picture unless the flash is on. - Lil Wayne
    }
}
