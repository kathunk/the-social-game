<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsTeamSwaps;
use App\Challenges\Support\Traits\HasTeamSwaps;
use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use Illuminate\Support\Collection;

class FlattenTheCurve extends BaseChallengeClass implements SupportsTeamSwaps
{
    use HasTeamSwaps;

    public static function key(): string
    {
        return 'flatten_the_curve';
    }

    public function dataArrayForState(): array
    {
        return ['swapper_ids' => []];
    }

    public function frontendComponent(Player $player): array
    {
        $average_team_size = round($this->challenge->game->teams->average(fn ($t) => $t->players->count()));
        $team_size = $player->team->players->count();
        $score = ($average_team_size - $team_size) * 5;

        if ($this->playerCanSwapTeams(player: $player)) {
            return $this->form()
                ->title('Flatten the Curve')
                ->subtitle('The current average team size is '.$average_team_size.'. At the end of the game, every team will get: ('.$average_team_size.' - {your_team_size}) * 5. For your current team, you will get: '.$score.' points.')
                ->teamSwap(teams: $this->availableTeams($player))
                ->build();
        }

        return $this->form()
            ->title('Flatten the Curve')
            ->subtitle('The current average team size is '.$average_team_size.'. At the end of the game, every team will get: ('.$average_team_size.' - {your_team_size}) * 5. For your current team, you will get: '.$score.' points.')
            ->build();
    }

    public function availableTeams(Player $player): Collection
    {
        return $player->game->teams->filter(fn ($t) => $t->id !== $player->team_id);
    }

    public function playerCanSwapTeams(?Player $player = null, ?PlayerState $player_state = null): bool
    {
        if ($player) {
            return ! in_array($player->id, $this->challenge->challenge_data['swapper_ids']);
        }

        if ($player_state) {
            return ! in_array($player_state->id, $this->challenge_state->challenge_data['swapper_ids']);
        }

        return false;
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $teams = $game_state->teams();

        $average_team_size = round($teams->average(fn ($t) => $t->player_ids->count()));

        $teams->each(function ($team) use ($average_team_size) {
            $team->addToScoreHistory(($average_team_size - $team->player_ids->count()) * 5, 'Flattened the curve');
        });
    }
}
