<?php

namespace App\Challenges\Classes;

use App\Challenges\Support\Interfaces\SupportsTeamSwaps;
use App\Challenges\Support\Traits\HasTeamSwaps;
use App\Models\Player;
use App\States\GameState;
use App\States\PlayerState;
use App\States\TeamState;
use Illuminate\Support\Collection;

class TeamBounty extends BaseChallengeClass implements SupportsTeamSwaps
{
    use HasTeamSwaps;

    const NAME = 'Bounty';

    const DESCRIPTION = 'Your team has been assigned 3 players from other teams as bounties: {3 players}. For each bounty you can convince to defect and join you, your team gains 15 points. But be careful - other teams are trying to recruit your teammates too!';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'team_bounty';
    }

    public function isInvalidForTemplate(array $challenges, array $modifiers, string $type, array $team_names)
    {
        $keys_for_first_challenge = collect($challenges)->first()['challenge_keys'];

        if (in_array(static::key(), $keys_for_first_challenge)) {
            return "Bounty cannot go first.";
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        return [
            'swapper_ids' => [],
            'team_bounties' => $team_bounties ?? [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $bounties = $this->challenge->challenge_data['team_bounties'][$player->team_id];

        $description = strtr(self::DESCRIPTION, [
            '{3 players}' => collect($bounties)->map(function ($id) {
                $player = Player::find($id);

                return $player->name.' ('.$player->team->name.')';
            })->implode(', '),
        ]);

        if ($this->playerCanSwapTeams(player: $player)) {
            return $this->form()
                ->title(self::NAME)
                ->subtitle($description)
                ->teamSwap($this->availableTeams($player))
                ->build();
        }

        return $this->form()
            ->title(self::NAME)
            ->subtitle($description)
            ->build();
    }

    public function availableTeams(Player $player): Collection
    {
        return $player->game->teams->filter(fn ($t) => $t->id !== $player->team_id);
    }

    public function playerCanSwapTeams(?Player $player = null, ?PlayerState $player_state = null): bool
    {
        if ($player) {
            return ! in_array($player->id, $this->challenge->fresh()->challenge_data['swapper_ids']);
        }

        if ($player_state) {
            return ! in_array($player_state->id, $this->challenge_state->fresh()->challenge_data['swapper_ids']);
        }

        return false;
    }

    public function onChallengeStarted(GameState $game_state)
    {
        // team_id => [player_id, player_id, player_id]
        $marked_as_bounty = [];

        $teams = $game_state->teams();

        // mark 3 random players from each team as bounties
        foreach ($teams as $team) {
            $marked_as_bounty[$team->id] = $team->player_ids->shuffle()->take(3)->all();
        }

        // assign 3 of those bounties to each team
        $team_bounties = [];

        foreach ($teams as $team) {
            // Get 3 other random teams
            $other_team_ids = collect($marked_as_bounty)
                ->reject(fn ($bounties) => count($bounties) === 0)
                ->keys()
                ->reject(fn ($id) => $id === $team->id)
                ->shuffle()
                ->take(3);

            // Pick one bounty from each selected other team
            $bounties = $other_team_ids->map(function ($other_team_id) use ($marked_as_bounty) {
                return collect($marked_as_bounty[$other_team_id])->random();
            });

            $team_bounties[$team->id] = $bounties->values()->all();
        }

        $this->challenge_state->challenge_data['team_bounties'] = $team_bounties;
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

        $this->challenge_state->fresh()->challenge_data['swapper_ids'][] = $player_state->id;

        $team_bounties = $this->challenge_state->challenge_data['team_bounties'][$team_state->id] ?? [];

        if (in_array($player_state->id, $team_bounties)) {
            $team_state->addToScoreHistory(15, "Recruited {$player_state->name} during the Bounty challenge");
        }
    }
}
