<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsTeamSwaps;
use App\Challenges\Support\Traits\HasTeamSwaps;
use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;
use Illuminate\Support\Collection;

class FlattenTheCurve extends BaseChallengeClass implements SupportsTeamSwaps
{
    use HasTeamSwaps;

    const NAME = 'Flatten the Curve';

    const DESCRIPTION = 'At the end of this challenge, every team will get: 
        ({average team size} - {size of team}) * 5. 
        Average team size: {average}. {team} size: {team_size}.
        {team} is on track to score {score} points.';

    const TYPE = 'team';

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

        $description = strtr(self::DESCRIPTION, [
            '{average}' => $average_team_size,
            '{team_size}' => $team_size,
            '{score}' => $score,
            '{team}' => $player->team->name,
        ]);

        return $this->form()
            ->title(self::NAME)
            ->subtitle($description)
            ->when(
                $this->playerCanSwapTeams(player: $player),
                fn ($form) => $form->teamSwap(teams: $this->availableTeams($player))
            )
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

    public function onPlayerJoinedTeam(
        PlayerState $player_state,
        TeamState $team_state,
        GameState $game_state,
        ?TeamState $previous_team = null,
    ) {
        if ($previous_team) {
            $this->challenge_state->challenge_data['swapper_ids'][] = $player_state->id;
        }
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
