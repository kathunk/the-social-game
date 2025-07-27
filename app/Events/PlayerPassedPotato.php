<?php

namespace App\Events;

use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayerOnTeam;
use App\States\ChallengeState;
use App\States\PlayerState;
use App\States\TeamState;
use Thunk\Verbs\Event;

class PlayerPassedPotato extends Event
{
    use HasChallenge, HasGame, HasPlayerOnTeam;

    public int $recipient_id;

    public function apply(ChallengeState $challenge)
    {
        $team_data = $challenge->challenge_data[$this->team_id];

        if (collect($team_data['all_holder_ids'])->contains($this->recipient_id)) {
            $challenge->challenge_data[$this->team_id]['status'] = 'failed';
            $challenge->challenge_data[$this->team_id]['potato_holder_id'] = null;

            return;
        }

        $challenge->challenge_data[$this->team_id]['all_holder_ids'][] = $this->recipient_id;

        $challenge->challenge_data[$this->team_id]['remaining_player_ids'] =
            collect($team_data['remaining_player_ids'])
                ->filter(fn ($id) => $id !== $this->recipient_id)
                ->toArray();

        if (collect($challenge->challenge_data[$this->team_id]['remaining_player_ids'])->count() === 0) {
            $challenge->challenge_data[$this->team_id]['status'] = 'succeeded';
            $challenge->challenge_data[$this->team_id]['potato_holder_id'] = null;

            return;
        }

        $challenge->challenge_data[$this->team_id]['potato_holder_id'] = $this->recipient_id;
    }

    public function applyToTeam(TeamState $team)
    {
        $team_data = $this->state(ChallengeState::class)->challenge_data[$team->id];

        if ($team_data['status'] === 'succeeded') {
            $team->addToScoreHistory(
                icon: '🥔',
                points: 50,
                description: 'Completed the hot potato challenge.',
            );
        }

        if ($team_data['status'] === 'failed') {
            $player = PlayerState::load($this->recipient_id);

            $team->addToScoreHistory(
                icon: '🥔',
                points: -50,
                description: "Failed the hot potato challenge. $player->name held the potato twice.",
            );
        }
    }

    public function handle()
    {
        $this->team()->updateModelWithStateData();
        $this->challenge()->updateModelWithStateData();
    }
}
