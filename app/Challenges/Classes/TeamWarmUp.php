<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsTeamSwaps;
use App\Challenges\Support\Traits\HasTeamSwaps;
use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;
use Illuminate\Support\Collection;

class TeamWarmUp extends BaseChallengeClass implements SupportsTeamSwaps
{
    use HasTeamSwaps;

    const NAME = 'Warm Up Round';

    const DESCRIPTION = 'Get comfortable. Recruit some more players to your team. Any time before the first real challenge begins, you may swap teams once.';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'team_warm_up';
    }

    public function dataArrayForState(): array
    {
        return ['swapper_ids' => []];
    }

    public function frontendComponent(Player $player): array
    {
        return $this->form()
            ->title(self::NAME)
            ->subtitle(self::DESCRIPTION)
            ->when($this->playerCanSwapTeams(player: $player), fn ($form) => $form->teamSwap($this->availableTeams($player)))
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
}
