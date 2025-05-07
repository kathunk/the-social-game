<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
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
        return ['ally_pairs' => []];
    }

    public function frontendComponent(Player $player): array
    {
        $ally = $this->ally($player);
        $elligible_partner_exists = $ally ? false : $this->elligiblePartners($player)->count() > 0;
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

    public function alliedPlayerIds(Player $player)
    {
        return collect($this->modifier->modifier_data['pairs'])
            ->reduce(function ($carry, $pair) {
                $carry[] = $pair['player_1_id'];
                $carry[] = $pair['player_2_id'];

                return $carry;
            }, []);
    }

    public function ally(Player $player)
    {
        return collect($this->modifier->modifier_data['pairs'])
            ->filter(fn($pair) => $pair['player_1_id'] === $player->id || $pair['player_2_id'] === $player->id)
            ->first()
            ?->map(function($pair) {
                if ($pair['player_1_id'] === $player->id) {
                    return Player::find($pair['player_2_id']);
                }

                return Player::find($pair['player_1_id']);
            });
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
}
