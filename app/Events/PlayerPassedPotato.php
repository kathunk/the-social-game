<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\TeamState;
use App\States\PlayerState;
use App\Events\Traits\HasGame;
use App\States\ChallengeState;
use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasPlayerOnTeam;

class PlayerPassedPotato extends Event
{
    use HasPlayerOnTeam, HasGame, HasChallenge;

    public int $recipient_id;

    public function apply(ChallengeState $challenge)
    {
        $team_data = $challenge->challenge_data[$this->team_id];

        $challenge->challenge_data[$this->team_id]['all_holder_ids'][] = $this->recipient_id;

        if (collect($team_data['all_holder_ids'])->filter(fn ($id) => $id === $this->recipient_id)->count() === 2) {
            $challenge->challenge_data[$this->team_id]['status'] = 'failed';
            $challenge->challenge_data[$this->team_id]['potato_holder_id'] = null;
            return;
        }

        $challenge->challenge_data[$this->team_id]['remaining_player_ids'] = 
            collect($team_data['remaining_player_ids'])->filter(fn ($id) => $id !== $this->recipient_id)->toArray();

        if (empty($team_data['remaining_player_ids'])) {
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
            $team->addToScoreHistory(50, "Completed the hot potato challenge.");
        }

        if ($team_data['status'] === 'failed') {
            $double_holder_id = collect($team_data['all_holder_ids'])
                ->filter(fn ($id) => array_count_values($team_data['all_holder_ids'])[$id] === 2)
                ->first();

            $player = PlayerState::load($double_holder_id);

            $team->addToScoreHistory(-50, "Failed the hot potato challenge. $player->name held the potato twice.");
        }
    }

    public function handle()
    {
        $this->team()->updateModelWithStateData();
        $this->challenge()->updateModelWithStateData();
    }
}
