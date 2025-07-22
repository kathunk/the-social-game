<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsTeamSwaps;
use App\Events\PlayerJoinedTeam;
use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Thunk\Verbs\Facades\Verbs;

class FlattenTheCurve extends BaseChallengeClass implements SupportsTeamSwaps
{
    const NAME = 'Tick tick boom';

    const DESCRIPTION = 'At the end of this challenge, every team will get: 
        ({average team size} - {size of team}) * 10. 
        Average team size: {average}. {team} size: {team_size}.
        {team} is on track to score {score} points.
        At some unspecified time during this challenge, you will no longer be able to swap teams.';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'flatten_the_curve';
    }

    public function dataArrayForState(): array
    {
        $start_time = $this->challenge->starts_at;
        $end_time = $this->challenge->ends_at;
        $duration = -$end_time->copy()->diffInSeconds($start_time->copy());
        $half = $duration / 2;
        $random_stop_time = $start_time->copy()->addSeconds($half + rand(0, $half));

        return ['swapper_ids' => [], 'stop_time' => $random_stop_time];
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

        $player_has_swapped = in_array($player->id, $this->challenge->challenge_data['swapper_ids']);
        $time_is_up = Carbon::parse($this->challenge->challenge_data['stop_time'])->isPast();

        return $this->form()
            ->title(self::NAME)
            ->subtitle($description)
            ->when(
                ! $player_has_swapped && ! $time_is_up,
                fn ($form) => $form->teamSwap(teams: $this->availableTeams($player))
            )
            ->when(
                $time_is_up,
                fn ($form) => $form->title('Team swapping is now locked. Good luck!')
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

    public function swapTeams(Player $player, array $params)
    {
        if (! $this instanceof SupportsTeamSwaps) {
            throw new \RuntimeException('Challenge class must implement SupportsTeamSwaps interface');
        }

        if (Carbon::parse($this->challenge->challenge_data['stop_time'])->isPast()) {
            throw new \Exception('Team swapping is locked');
        }

        PlayerJoinedTeam::fire(
            player_id: $player->id,
            team_id: (int) $params['team_id'],
            game_id: $player->game_id,
            previous_team_id: $player->team_id,
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
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
            $team->addToScoreHistory(($average_team_size - $team->player_ids->count()) * 5, '📈 Flattened the curve. Average team size was '.$average_team_size.'. '.$team->name.' size was '.$team->player_ids->count().'.');
        });
    }
}
