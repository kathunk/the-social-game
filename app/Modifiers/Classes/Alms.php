<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use App\States\GameState;

class Alms extends BaseModifierClass
{
    const NAME = 'Alms';

    const DESCRIPTION = 'After each challenge is resolved, the player(s) at the bottom of the scoreboard will gain 1 hidden point.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'alms';
    }

    public function dataArrayForState(): array
    {
        return $this->modifier_state->game()->challenges()
            ->map(fn ($challenge) => [$challenge->id => ['player_ids' => null]])->toArray();
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
        $lowest_score = $players->min(fn ($player) => $player->score());

        $players->each(function ($player) use ($lowest_score) {
            if ($player->score() === $lowest_score) {
                $player->addToScoreHistory(1, '🪙 Collected alms for being at the bottom of the scoreboard', is_hidden: true);
            }
        });
    }
}
