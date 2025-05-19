<?php

namespace App\Rules;

use App\Models\Challenge;
use App\Models\Player;
use Illuminate\Support\Facades\Auth;

class NuclearCode extends StringableRule
{
    public static function keyword(): string
    {
        return 'nuclear_code';
    }

    public static function validate(string $attribute, $value, array $parameters, $validator): bool
    {
        $player = Auth::user()?->currentPlayer;
        $challenge = Auth::user()?->currentGame?->currentChallenge;

        if (! $player || ! $challenge) {
            return false;
        }

        if (! $player instanceof Player || ! $challenge instanceof Challenge) {
            return false;
        }

        $team_id = $player->team_id;

        if (! isset($challenge->challenge_data[$team_id])) {
            return false;
        }

        $ally_team_id = $challenge->challenge_data[$team_id]['ally_team_id'];

        if (! isset($challenge->challenge_data[$ally_team_id])) {
            return false;
        }

        $ally_code = $challenge->challenge_data[$ally_team_id]['code'];

        return $value === $ally_code;
    }
}
