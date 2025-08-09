<?php

namespace App\Events;

use Thunk\Verbs\Event;
use App\States\PlayerState;
use App\Events\Traits\HasGame;
use App\Events\Traits\HasPlayer;
use App\Events\Traits\HasModifier;
use App\Events\Traits\HasChallenge;

class PlayerSubmittedTierListGuess extends Event
{
    use HasPlayer, HasGame, HasChallenge, HasModifier;

    public array $answer_key;

    public array $guesses;

    public function applyToPlayer(PlayerState $player)
    {
        $opponents = $player->game()->players()->where('id', '!=', $player->id);

        foreach ($this->guesses as $guess) {
            $guessed_tier = $guess['guessed_tier'];
            $correct_tier = $guess['actual_tier'];
            $original_submission = $this->answer_key[$guess['actual_tier']];

            $map = ['a' => 0, 'b' => 1, 'c' => 2, 'd' => 3, 'f' => 4];
            $distance = abs($map[strtolower($guessed_tier)] - $map[strtolower($correct_tier)]);

            $points = 2 - $distance;
            
            $emoji = match($distance) {
                0 => '🥳',
                1 => '🧐',
                2 => '😥',
                default => '😩',
            };

            $opponent_name = $opponents->firstWhere('id', $original_submission['player_id'])->name;
            $original_submission_value = $original_submission['value'];

            $player_score_description = "Guessed that $original_submission_value was $guessed_tier-tier (submitted by $opponent_name at $correct_tier-tier)";
            $player->addToScoreHistory($emoji, $points, $player_score_description);
        }
    }

    public function handle()
    {
        $this->player()->updateModelWithStateData();
    }
}
