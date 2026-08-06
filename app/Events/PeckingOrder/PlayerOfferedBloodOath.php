<?php

namespace App\Events\PeckingOrder;

use App\Events\Traits\HasActivePlayer;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\States\GameState;
use App\States\ModifierState;
use Thunk\Verbs\Event;

class PlayerOfferedBloodOath extends Event
{
    use HasActivePlayer, HasGame, HasModifier;

    public int $oath_offer_id;

    public function validate()
    {
        $data = $this->state(ModifierState::class)->modifier_data;

        $this->assert(
            ! in_array($this->player()->id, $data['lone_wolves']),
            'You have already made an oath of solitude',
        );

        $this->assert(
            ! array_key_exists($this->player()->id, $data['pairs']),
            'You are already in an alliance',
        );

        $this->assert(
            $this->state(GameState::class)->player_ids->contains($this->oath_offer_id),
            'The player you are offering an oath to does not exist',
        );

        $previous_upvotee_ids = $this->state(GameState::class)->challenges()
            ->map(function ($challenge) {
                $ballot = $challenge->challenge_data['votes'][$this->player_id] ?? [];

                return $ballot['upvote_player_id'] ?? null;
            })->filter()->unique()->values()->toArray();

        $this->assert(
            ! in_array($this->oath_offer_id, $previous_upvotee_ids),
            'You cannot offer an oath to someone you have already upvoted',
        );
    }

    public function apply(ModifierState $modifier)
    {
        $modifier->modifier_data['offers'][$this->player()->id] = $this->oath_offer_id;

        if (
            array_key_exists($this->oath_offer_id, $modifier->modifier_data['offers'])
            && $modifier->modifier_data['offers'][$this->oath_offer_id] === $this->player_id
        ) {
            $modifier->modifier_data['pairs'][$this->player_id] = $this->oath_offer_id;
            $modifier->modifier_data['pairs'][$this->oath_offer_id] = $this->player_id;

            $modifier->modifier_data['offers'][$this->player()->id] = null;
            $modifier->modifier_data['offers'][$this->oath_offer_id] = null;
        }
    }

    public function handle()
    {
        $this->modifier()->updateModelWithStateData();
        $this->player()->updateModelWithStateData();
    }
}
