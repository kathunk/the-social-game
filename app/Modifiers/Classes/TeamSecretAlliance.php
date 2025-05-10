<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use App\States\GameState;
use App\States\TeamState;
use App\States\PlayerState;
use App\States\ModifierState;
use Thunk\Verbs\Facades\Verbs;
use Illuminate\Support\Facades\Route;
use App\Events\PlayerResignedInTeamGame;
use App\Events\PlayerAssignedSecretTeamAlly;
use App\Events\PlayerAssignedSecretAllyInTeamGame;

class TeamSecretAlliance extends BaseModifierClass
{
    const NAME = 'Star crossed allies';

    const DESCRIPTION = 'You have been randomly assigned a secret alliance with {player_name}. They are currently on {ally_team_name}. If at any point, you and {player_name} join a new team together, that team will receive +5 points.';

    const TYPE = 'team';

    const IS_SECRET = true;

    public static function key(): string
    {
        return 'team_secret_alliance';
    }

    public function dataArrayForState(): array
    {
        return ['pairs' => []];
    }

    public function frontendComponent(Player $player): array
    {
        $pair_data = new TeamSecretAlliancePairData(player: $player, modifier: $this->modifier);
        $ally = $pair_data->ally();
        $elligible_partner_exists = $pair_data->ally() ? false : $this->elligiblePartners($player)->count() > 0;
        $has_connected = $pair_data->hasConnected();
        $player_is_active = $player->status === 'active';
        $player_is_on_dashboard = Route::currentRouteName() === 'game-dashboard';
        $player_is_lucky = rand(0, 100) > 0;

        $description = strtr(self::DESCRIPTION, [
            '{player_name}' => $ally->name,
            '{ally_team_name}' => $ally->team->name,
        ]);

        if ($player_is_active && $player_is_on_dashboard && ! $ally && $elligible_partner_exists && $player_is_lucky) {
            return $this->form()
                ->title(static::NAME)
                ->subtitle("You discovered a secret! A friend is waiting for you.")
                ->buttonGroup()
                    ->button('Learn more', 'learnMore')
                ->endGroup()
                ->build();
        }

        if ($has_connected) {
            return $this->form()
                ->title(static::NAME)
                ->subtitle('Congratulations! You have found your star crossed ally, and your team was rewarded.')
                ->build();
        }

        if ($player_is_active && ! $player_is_on_dashboard && $ally) {
            return $this->form()
                ->title(static::NAME)
                ->subtitle($description)
                ->build();
        }

        if (! $elligible_partner_exists) {
            return $this->form()
                ->title(static::NAME)
                ->subtitle('Unfortunately there are no eligible partners for you right now. Try again later.')
                ->build();
        }

        return [];
    }

    public function learnMore(Player $player, array $params)
    {
        return redirect()->route('games.secrets', ['game' => $player->game, 'modifier' => $this->modifier]);
    }

    public function elligiblePartners(Player $player)
    {
        $paired_player_ids = collect($this->modifier->modifier_data['pairs'])
            ->reduce(function ($carry, $pair) {
                $carry[] = $pair['player_1_id'];
                $carry[] = $pair['player_2_id'];

                return $carry;
            }, []);

        return $player->game->players->where('status', 'active')
            ->reject(fn($p) => $paired_player_ids->contains($p->id))
            ->filter(fn($p) => $p->id === $player->id)
            ->filter(fn($p) => $p->team_id !== null && $p->team_id === $player->team_id);
    }

    public function onSecretDiscovered(Player $player)
    {
        $paired_player_ids = collect($this->modifier->modifier_data['pairs'])
            ->reduce(function ($carry, $pair) {
                $carry[] = $pair['player_1_id'];
                $carry[] = $pair['player_2_id'];

                return $carry;
            }, []);

        if ($paired_player_ids->contains($player->id)) {
            return;
        }

        $unallied_player_ids = $this->elligiblePartners($player)->pluck('id');

        if ($unallied_player_ids->count() === 0) {
            return;
        }

        $ally_id = $unallied_player_ids->random();

        PlayerAssignedSecretAllyInTeamGame::fire(
            player_id: $player->id,
            ally_id: $ally_id,
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
        ); 

        Verbs::commit();

        return redirect()->route('games.secrets', [$player->game, $this->modifier]);
    }

    public function onPlayerJoinedTeam(
        PlayerState $player_state,
        TeamState $team_state,
        GameState $game_state,
        ModifierState $modifier_state,
        ?TeamState $previous_team = null,
    ) {
        $pair_data = $this->pairData($player_state);
        $ally = $this->ally($player_state);

        if ( ! $pair_data) {
            return;
        }

        if ($ally->team_id !== $team_state->id) {
            return;
        }

        if ($pair_data['has_connected']) {
            return;
        }

        if ($pair_data['player_1_original_team_id'] === $team_state->id || $pair_data['player_2_original_team_id'] === $team_state->id) {
            return;
        }

        $team_state->addToScoreHistory(5, 'Secret alliance bonus');

        $modifier_state->modifier_data['pairs'] = collect($modifier_state->modifier_data['pairs'])
            ->map(function ($pair) use ($pair_data) {
                if ($pair['player_1_id'] === $pair_data['player_1_id'] && $pair['player_2_id'] === $pair_data['player_2_id']) {
                    $pair['has_realized_reward'] = true;
                }

                return $pair;
            });
    }
}

class TeamSecretAlliancePairData
{
    public function __construct(
        public int $player_id,
        public ?ModifierState $modifier_state = null,
        public ?Modifier $modifier = null,
    ) {
        return $this->pairData();
    }

    public function pairData()
    {
        if ( ! $this->modifier_state || ! $this->modifier) {
            return null;
        }

        if ($this->modifier) {
            return collect($this->modifier->modifier_data['pairs'])
                ->filter(fn($pair) => $pair['player_1_id'] === $this->player_id || $pair['player_2_id'] === $this->player_id)
                ->first();
        }

        return collect($this->modifier_state->modifier_data['pairs'])
            ->filter(fn($pair) => $pair['player_1_id'] === $this->player_id || $pair['player_2_id'] === $this->player_id)
            ->first();
    }

    public function hasConnected()
    {
        return $this->pairData()['has_connected'];
    }

    public function originalTeamIds()
    {
        return [
            $this->pairData()['player_1_original_team_id'],
            $this->pairData()['player_2_original_team_id'],
        ];
    }

    public function ally()
    {
        return $this->pairData()['player_1_id'] === $this->player_id 
            ? $this->pairData()['player_2_id'] 
            : $this->pairData()['player_1_id'];
    }
}

