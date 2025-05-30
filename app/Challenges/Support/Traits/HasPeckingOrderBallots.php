<?php

namespace App\Challenges\Support\Traits;

use App\Challenges\Support\Interfaces\SupportsPeckingOrderBallots;
use App\Events\PlayerSubmittedPeckingOrderBallot;
use App\Models\Player;
use App\Modifiers\Classes\BloodOaths;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Facades\Verbs;

trait HasPeckingOrderBallots
{
    public function vote(Player $player, array $params)
    {
        if (! $this instanceof SupportsPeckingOrderBallots) {
            throw new \RuntimeException('Challenge class must implement SupportsPeckingOrderBallots interface');
        }

        PlayerSubmittedPeckingOrderBallot::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            game_id: $this->challenge->game_id,
            upvote_player_id: (int) $params['upvote_player_id'],
            downvote_player_id: (int) $params['downvote_player_id'],
        );

        Verbs::commit();
    }

    public function playerCanVote(?Player $player = null, ?PlayerState $player_state = null): bool
    {
        if ($player) {
            return $this->challenge->challenge_data['votes'][$player->id]['upvote_player_id'] === null
                && $this->challenge->challenge_data['votes'][$player->id]['downvote_player_id'] === null;
        }

        if ($player_state) {
            return $this->challenge_state->challenge_data['votes'][$player_state->id]['upvote_player_id'] === null
                && $this->challenge_state->challenge_data['votes'][$player_state->id]['downvote_player_id'] === null;
        }

        return false;
    }

    public function applyVotesToScore(GameState $game_state)
    {
        $votes = $this->challenge_state->challenge_data['votes'];

        $players = $game_state->players();

        $players->each(function ($player) use ($votes) {
            $upvotes_received = collect($votes)
                ->filter(fn ($v) => $v['upvote_player_id'] === $player->id)
                ->count();

            $downvotes_received = collect($votes)
                ->filter(fn ($v) => $v['downvote_player_id'] === $player->id)
                ->count();

            if ($upvotes_received > 0) {
                $player->addToScoreHistory($upvotes_received, 'Received upvotes');
            }

            if ($downvotes_received > 0) {
                $player->addToScoreHistory(-$downvotes_received, 'Received downvotes');
            }
        });
    }

    public function upvoteTargets(Player $player)
    {
        $modifiers = $this->challenge->game->modifiers;

        $is_blood_oath_game = $modifiers->where('class_key', BloodOaths::key())->count() > 0;

        if (! $is_blood_oath_game) {
            return $this->challenge->game->players->reject(fn ($p) => $p->id === $player->id);
        }

        $oath_data = $modifiers->firstWhere('class_key', BloodOaths::key())->modifier_data;

        $has_buddy = $oath_data['pairs'][$player->id] ?? false;

        if (! $has_buddy) {
            return $this->challenge->game->players->reject(fn ($p) => $p->id === $player->id);
        }

        $buddy_id = $oath_data['pairs'][$player->id];

        return $this->challenge->game->players->reject(fn ($p) => $p->id === $player->id || $p->id === $buddy_id);
    }

    public function downvoteTargets(Player $player)
    {
        return $this->challenge->game->players->reject(fn ($p) => $p->id === $player->id);
    }
}
