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

    const DESCRIPTION = 'You may swap teams once during this challenge. If you leave your team, you will take your portion of the team\'s points with you. Currently you carry {points} points (hidden points are not included). However, the scoreboard will be hidden for this entire challenge.';

    const TYPE = 'team';

    const HIDE_SCOREBOARD = true;

    public static function key(): string
    {
        return 'the_great_realignment';
    }

    public function dataArrayForState(): array
    {
        return ['swapper_ids' => []];
    }

    public function frontendComponent(Player $player): array
    {
        $team_score = $player->team->score;
        $points = $team_score / $player->team->players->count();

        $description = strtr(self::DESCRIPTION, [
            '{points}' => round($points),
        ]);

        if ($this->playerCanSwapTeams(player: $player)) {
            return $this->form()
                ->title(self::NAME)
                ->subtitle($description)
                ->teamSwap(teams: $this->availableTeams($player))
                ->build();
        }

        return $this->form()
            ->title(self::NAME)
            ->subtitle("You've made your choice. Good luck!")
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
        $points = round($previous_team_score / $player_count);

        $team_state->addToScoreHistory($points, '👋 '.$player_state->name.' joined the team');
        $previous_team->addToScoreHistory(-$points, '👻 '.$player_state->name.' left the team');
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
