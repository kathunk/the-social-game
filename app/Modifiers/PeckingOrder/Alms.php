<?php

namespace App\Modifiers\PeckingOrder;

use App\Modifiers\BaseModifierClass;
use App\Models\Player;
use App\States\GameState;

class Alms extends BaseModifierClass
{
    const NAME = 'Alms';

    const DESCRIPTION = 'After each challenge is resolved, the player(s) with the lowest score (including hidden points) will gain 1 hidden point.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'alms';
    }

    public function frontendComponent(Player $player): array
    {
        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->build();
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $players = $game_state->players();
        $lowest_score = $players->min(fn ($player) => $player->score(include_hidden: true));

        $players->each(function ($player) use ($lowest_score) {
            if ($player->score(include_hidden: true) === $lowest_score) {
                $player->addToScoreHistory(
                    icon: '🪙',
                    points: 1,
                    description: 'Collected alms for being at the bottom of the scoreboard',
                    is_hidden: true,
                );
            }
        });
    }
}
