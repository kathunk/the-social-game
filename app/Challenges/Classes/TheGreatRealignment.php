<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsTeamSwaps;
use App\Challenges\Support\Traits\HasTeamSwaps;
use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;
use Illuminate\Support\Collection;

class TheGreatRealignment extends BaseChallengeClass implements SupportsTeamSwaps
{
    use HasTeamSwaps;

    const NAME = 'The Great Realignment';

    const DESCRIPTION = 'You may swap teams once during this challenge. If you leave your team, you will take your portion of the team\'s points with you. Currently you carry {points} points and {hidden_points} hidden points. However, the scoreboard will not update until the end of the challenge.';

    const TYPE = 'team';

    const HIDE_SCOREBOARD = true;

    public static function key(): string
    {
        return 'the_great_realignment';
    }

    public function dataArrayForState(): array
    {
        $previous_scoreboard = $this->challenge->game->teams
            ->sortByDesc('score')
            ->map(fn ($team) => [
                'Name' => $team->name,
                'Players' => $team->players->count(),
                'Score' => $team->score,
            ])
            ->toArray();

        return ['swapper_ids' => [], 'previous_scoreboard' => $previous_scoreboard];
    }

    public function frontendComponent(Player $player): array
    {
        $team = $player->team;
        $team_score = $team->score;
        $points = (int) round($team_score / $team->players->count());
        $total_hidden_points = $team->hidden_score - $team_score;
        $hidden_points = (int) round($total_hidden_points / $team->players->count());

        $description = strtr(self::DESCRIPTION, [
            '{points}' => $points,
            '{hidden_points}' => $hidden_points,
        ]);

        $scoreboard = collect($this->challenge->challenge_data['previous_scoreboard'])
            ->sortByDesc('Score')
            ->toArray();

        if ($this->playerCanSwapTeams(player: $player)) {
            return $this->form()
                ->title(self::NAME)
                ->subtitle($description)
                ->teamSwap(teams: $this->availableTeams($player))
                ->divider()
                ->title('Scoreboard as of the start of this challenge:')
                ->table(headers: ['Name', 'Score', 'Players'], rows: $scoreboard)
                ->build();
        }

        return $this->form()
            ->title(self::NAME)
            ->subtitle("You've made your choice. Good luck!")
            ->divider()
            ->title('Scoreboard as of the start of this challenge:')
            ->table(headers: ['Name', 'Players', 'Score'], rows: $scoreboard)
            ->build();
    }

    public function availableTeams(Player $player): Collection
    {
        return $player->game->teams->filter(fn ($t) => $t->id !== $player->team_id);
    }

    public function onPlayerJoinedTeam(
        PlayerState $player_state,
        TeamState $team_state,
        GameState $game_state,
        ?TeamState $previous_team = null,
    ) {
        if (! $previous_team) {
            return;
        }

        $this->challenge_state->challenge_data['swapper_ids'][] = $player_state->id;

        $previous_team_score = $previous_team->score();
        $player_count = max(1, $previous_team->player_ids->count());
        $points = (int) round($previous_team_score / $player_count);
        $total_hidden_points = $previous_team->score(include_hidden: true) - $previous_team_score;
        $hidden_points = (int) round($total_hidden_points / $player_count);

        $team_state->addToScoreHistory($points, '👋 '.$player_state->name.' joined during the Great Realignment');
        $previous_team->addToScoreHistory(-$points, '👻 '.$player_state->name.' left during the Great Realignment');

        if ($hidden_points !== 0) {
            $team_state->addToScoreHistory($hidden_points, '👋 '.$player_state->name.' joined during the Great Realignment', is_hidden: true);
            $previous_team->addToScoreHistory(-$hidden_points, '👻 '.$player_state->name.' left during the Great Realignment', is_hidden: true);
        }
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
}
