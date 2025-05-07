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
        return ['ally_pair_ids' => []];
    }

    public function frontendComponent(Player $player): array
    {
        // @todo delete this
        $ally = Player::where('game_id', $player->game_id)->where('id', '!=', $player->id)->inRandomOrder()->first();

        $description = strtr(self::DESCRIPTION, [
            '{player_name}' => $ally->name,
            // '{ally_team_name}' => $ally->team->name,
        ]);

        $player_is_active = $player->status === 'active';
        $player_is_on_dashboard = Route::currentRouteName() === 'game-dashboard';
        // @todo check if they already have an ally
        $player_has_ally = false;
        $player_is_lucky = rand(0, 100) > 0;

        if ($player_is_active && $player_is_on_dashboard && ! $player_has_ally && $player_is_lucky) {
            return $this->form()
                ->title(static::NAME)
                ->subtitle("You discovered a secret! A friend is waiting for you.")
                ->buttonGroup()
                    ->button('Learn more', 'learnMore')
                ->endGroup()
                ->build();
        }

        if ($player_is_active && ! $player_is_on_dashboard) {
            return $this->form()
                ->title(static::NAME)
                ->subtitle($description)
                ->build();
        }

        return [];
    }

    public function learnMore(Player $player, array $params)
    {
        return redirect()->route('games.secrets', ['game' => $player->game, 'modifier' => $this->modifier]);
    }

    public function onSecretDiscovered(Player $player)
    {
        $allied_player_ids = collect($this->modifier->modifier_data['ally_pair_ids']);

        if ($allied_player_ids->contains($player->id)) {
            return;
        }

        $unallied_player_ids = $player->game->players->where('status', 'active')
            ->get()
            ->reject(fn($p) => $allied_player_ids->contains($p->id))
            ->filter(fn($p) => $p->id === $player->id);

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
