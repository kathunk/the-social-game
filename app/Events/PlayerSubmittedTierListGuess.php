<?php

namespace App\Events;

use App\Events\Traits\HasChallenge;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasPlayer;
use App\States\ChallengeState;
use App\States\GameState;
use App\States\PlayerState;
use Thunk\Verbs\Event;

class PlayerSubmittedTierListGuess extends Event
{
    use HasChallenge, HasGame, HasModifier, HasPlayer;

    public array $answer_key;

    public array $guesses;

    public function applyToChallenge(ChallengeState $challenge)
    {
        $challenge->challenge_data['has_submitted'][] = $this->player_id;
        $challenge->challenge_data['results'][$this->player_id] = $this->results();
    }

    public function applyToPlayer(PlayerState $player)
    {
        $opponents = $player->game()->players()->where('id', '!=', $player->id);

        foreach ($this->results() as $result) {
            $opponent = $opponents->firstWhere('id', $result['opponent_id']);
            // $player->addToScoreHistory($result['emoji'], $result['points'], $result['player_score_description']);
            // $opponent->addToScoreHistory($result['emoji'], floor($result['points'] * 0.5), $result['opponent_score_description']);
        }
    }

    public function handle()
    {
        $this->challenge()->updateModelWithStateData();
        $this->game()->players->each(fn ($player) => $player->updateModelWithStateData());
    }

    public function results()
    {
        $opponents = $this->state(GameState::class)->players()->where('id', '!=', $this->player_id);

        $results = [];

        foreach ($this->guesses as $guess) {
            $guessed_tier = $guess['guessed_tier'];
            $correct_tier = $guess['actual_tier'];
            $original_submission = $this->answer_key[$guess['actual_tier']];
            $opponent = $opponents->firstWhere('id', $original_submission['player_id']);

            $map = ['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3, 'f' => 4];
            $distance = abs($map[strtolower($guessed_tier)] - $map[strtolower($correct_tier)]);

            $points = 2 - $distance;

            $emoji = match ($distance) {
                0 => '🥳',
                1 => '🧐',
                2 => '😥',
                default => '😩',
            };

            $opponent_name = $opponent->name;
            $original_submission_value = $original_submission['value'];
            $player = $this->state(PlayerState::class);

            $player_score_description = "Guessed that $original_submission_value was $guessed_tier-tier (submitted by $opponent_name at $correct_tier-tier)";
            $opponent_score_description = "$player->name guessed that $original_submission_value was $correct_tier-tier (you ranked it as $guessed_tier-tier)";

            $results[] = [
                'opponent_id' => $opponent->id,
                'opponent_name' => $opponent_name,
                'original_submission_value' => $original_submission_value,
                'guessed_tier' => $guessed_tier,
                'correct_tier' => $correct_tier,
                'points' => $points,
                'emoji' => $emoji,
                'player_score_description' => $player_score_description,
                'opponent_score_description' => $opponent_score_description,
            ];
        }

        return $results;
    }
}
