<?php

namespace App\Modifiers\Classes;

use App\Models\Player;

class RedVsBlueTeamTracker extends BaseModifierClass
{
    const NAME = 'Teams';

    const DESCRIPTION = 'The team tracker for Red vs Blue.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'red_vs_blue_team_tracker';
    }

    public function dataArrayForState(): array
    {

    
        return $this->modifier->game->teams->map(fn ($team) => [
            'name' => $team->name,
            'score' => $team->score,
            'hidden_score' => $team->hidden_score,
        ])->toArray();
    }

    public function frontendComponent(Player $player): array
    {
        $teams = $this->modifier->game->teams()->with('players')->get();

        return $this->form()
            ->table(
                headers: ['Team', 'Players'], 
                rows: $teams->map(fn ($team) => [
                    'name' => $team->name,
                    'players' => $team->players->sortBy('name')->pluck('name')->implode(', '),
                ]
            )->toArray())
            ->build();
    }
}
