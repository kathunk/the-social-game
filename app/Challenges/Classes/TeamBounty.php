<?php

namespace App\Challenges\Classes;

use App\Challenges\Dtos\TeamBountyData;
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

    const DESCRIPTION = 'Your team has been assigned 3 players from other teams as bounties: {3 players}. For each bounty you can convince to defect and join you, your team gains 25 points. But be careful - other teams are trying to recruit your teammates too!';

    const TYPE = 'team';

    public ?string $challenge_data_class = TeamBountyData::class;

    public static function key(): string
    {
        return 'team_bounty';
    }

    public function isInvalidForTemplate(array $challenges, array $modifiers, string $type, array $team_names)
    {
        $keys_for_first_challenge = collect($challenges)->first()['challenge_keys'];

        if (in_array(static::key(), $keys_for_first_challenge)) {
            return 'Bounty cannot go first.';
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        return [
            'swapper_ids' => [],
            'team_bounties' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $bounties = $this->challenge->challenge_data['team_bounties'][$player->team_id];

        $description = strtr(self::DESCRIPTION, [
            '{3 players}' => collect($bounties)->map(function ($id) {
                $player = Player::find($id);

                if (in_array($player->id, $this->challenge->challenge_data['swapper_ids'])) {
                    return $player->name.' (ineligible: already moved to '.$player->team->name.')';
                }

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
        // Empty implementation as this logic has been moved to TeamBountyData::fromGameAndChallenge
        // The challenge data is now set deterministically during event creation
        // and applied to challenge_data in ChallengeStarted::applyToChallenge
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
            $team_state->addToScoreHistory(25, "💰 Recruited {$player_state->name} during the Bounty challenge");
        }
    }
}
