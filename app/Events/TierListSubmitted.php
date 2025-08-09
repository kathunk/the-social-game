<?php

namespace App\Events;

use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\States\ChallengeState;
use App\States\ModifierState;
use Thunk\Verbs\Event;

class TierListSubmitted extends Event
{
    use HasChallenge, HasGame, HasModifier, HasPlayer;

    public array $submissions;

    public function validate()
    {
        $this->assert(
            collect($this->submissions)->count() === collect($this->submissions)->unique('value')->count(),
            'Submissions must each be unique'
        );

        $this->assert(
            collect($this->state(ModifierState::class)->modifier_data['submissions'])
                ->filter(fn ($submission) => $submission['player_id'] === $this->player_id &&
                    $submission['category'] === $this->submissions[0]['category']
                )
                ->isEmpty(),
            'Player has already submitted this category'
        );
    }

    public function applyToModifier(ModifierState $modifier)
    {
        $modifier->modifier_data['submissions'] = [
            ...$modifier->modifier_data['submissions'],
            ...$this->submissions,
        ];
    }

    public function applyToChallenge(ChallengeState $challenge)
    {
        $player_submissions = collect($this->state(ModifierState::class)->modifier_data['submissions'])
            ->filter(fn ($submission) => $submission['player_id'] === $this->player_id);

        if ($player_submissions->count() === 15) {
            $challenge->challenge_data['has_submitted'][] = $this->player_id;
        }
    }

    public function handle()
    {
        $this->modifier()->updateModelWithStateData();
        $this->challenge()->updateModelWithStateData();
    }
}
