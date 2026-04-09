<?php

namespace App\Modifiers\Classes\Laracon2025;

use App\Modifiers\Classes\BaseModifierClass;
use App\Events\Laracon2025\PlayerAssignedSecretAllyInTeamGame;
use App\Models\Game;
use App\Models\Modifier;
use App\Models\Player;
use App\States\GameState;
use App\States\ModifierState;
use App\States\PlayerState;
use App\States\TeamState;
use Thunk\Verbs\Facades\Verbs;

class TeamSecretAlliance extends BaseModifierClass
{
    const NAME = 'Star crossed allies';

    const DESCRIPTION = "You have been randomly assigned a secret alliance with {player_name}.
        They were on {ally_team_name} when you were assigned.
        If at any point, you and {player_name} join a new team together,
        that team will receive 5 hidden points that will not be revealed until the end of the game.
        Note that this will not take effect if either of you simply joins the other's current team.
        You must both join a new team together.";

    const TYPE = 'team';

    public static function key(): string
    {
        return 'team_secret_alliance';
    }

    public function dataArrayForState(?Game $game = null): array
    {
        return ['pairs' => []];
    }

    public function frontendComponent(Player $player): array
    {
        $pair_data = new TeamSecretAlliancePairData(player_id: $player->id, modifier: $this->modifier);
        $ally = $pair_data->ally();
        $player_is_active = $player->status === 'active';
        $player_is_lucky = rand(0, 100) > 90;

        if ($ally || ($player_is_active && $player_is_lucky && $player->team_id)) {
            return $this->form()
                ->title(static::NAME)
                ->subtitle('You discovered a secret! A friend is waiting for you.')
                ->buttonGroup()
                ->button('Learn more', 'learnMore')
                ->endGroup()
                ->build();
        }

        return [];
    }

    public function frontendComponentForDedicatedPage(Player $player): array
    {
        $pair_data = new TeamSecretAlliancePairData(player_id: $player->id, modifier: $this->modifier);
        $ally = $pair_data->ally();
        $has_connected = $pair_data->hasConnected();
        $player_is_active = $player->status === 'active';

        if ($has_connected) {
            return $this->form()
                ->title(static::NAME)
                ->subtitle('Congratulations! You have found your star crossed ally, and your team was rewarded.')
                ->build();
        }

        if ($ally?->status === 'resigned') {
            return $this->form()
                ->title(static::NAME)
                ->subtitle('Sadly, your ally resigned from the game. It was not meant to be.')
                ->build();
        }

        $description = strtr(self::DESCRIPTION, [
            '{player_name}' => $ally?->name,
            '{ally_team_name}' => $ally?->team->name,
        ]);

        if ($player_is_active && $ally) {
            return $this->form()
                ->title(static::NAME)
                ->subtitle($description)
                ->build();
        }

        if (! $player->team_id) {
            return $this->form()
                ->subtitle('Join a team before you can discover your secret ally.')
                ->build();
        }

        $elligible_partner_exists = $pair_data->ally() ? false : $this->elligiblePartners($player)->count() > 0;

        if (! $elligible_partner_exists) {
            return $this->form()
                ->title(static::NAME)
                ->subtitle('Unfortunately there are no eligible partners for you right now. Try again later.')
                ->build();
        }

        if (! $player_is_active) {
            return $this->form()
                ->subtitle('You are not currently active. Got nothing for you, head back to camp.')
                ->build();
        }

        return [];
    }

    public function learnMore(Player $player, array $params)
    {
        return redirect()->route('games.mods', ['game' => $player->game, 'modifier' => $this->modifier]);
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
            ->reject(fn ($p) => collect($paired_player_ids)->contains($p->id))
            ->filter(fn ($p) => $p->id !== $player->id)
            ->filter(fn ($p) => $p->team_id !== null && $p->team_id !== $player->team_id);
    }

    public function onSecretDiscovered(Player $player)
    {
        if ($player->team_id === null) {
            return;
        }

        $paired_player_ids = collect($this->modifier->modifier_data['pairs'])
            ->reduce(function ($carry, $pair) {
                $carry[] = $pair['player_1_id'];
                $carry[] = $pair['player_2_id'];

                return $carry;
            }, []);

        if (collect($paired_player_ids)->contains($player->id)) {
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

        return redirect()->route('games.mods', [$player->game, $this->modifier]);
    }

    public function onPlayerJoinedTeam(
        PlayerState $player_state,
        TeamState $team_state,
        GameState $game_state,
        ModifierState $modifier_state,
        ?TeamState $previous_team = null,
    ) {
        $pair_data = new TeamSecretAlliancePairData(player_id: $player_state->id, modifier_state: $modifier_state);
        $ally = $pair_data->ally();

        if (! $ally) {
            return;
        }

        if ($ally->team_id !== $team_state->id) {
            return;
        }

        if ($pair_data->hasConnected()) {
            return;
        }

        if (collect($pair_data->originalTeamIds())->contains($team_state->id)) {
            return;
        }

        $team_state->addToScoreHistory(
            icon: '🤝',
            points: 5,
            description: $player_state->name.' and '.$ally->name.' were secret allies',
            is_hidden: true,
        );

        $modifier_state->modifier_data['pairs'] = collect($modifier_state->modifier_data['pairs'])
            ->map(function ($pair) use ($player_state) {
                if ($pair['player_1_id'] === $player_state->id || $pair['player_2_id'] === $player_state->id) {
                    $pair['has_connected'] = true;
                }

                return $pair;
            })
            ->toArray();
    }
}

class TeamSecretAlliancePairData
{
    protected ?array $pair;

    public function __construct(
        public int $player_id,
        public ?ModifierState $modifier_state = null,
        public ?Modifier $modifier = null,
    ) {
        $this->pair = $this->pairData();
    }

    protected function pairData(): ?array
    {
        $pairs = $this->modifier
            ? collect($this->modifier->modifier_data['pairs'])
            : collect($this->modifier_state->modifier_data['pairs']);

        return collect($pairs)
            ->first(fn ($pair) => in_array($this->player_id, [$pair['player_1_id'], $pair['player_2_id']]));
    }

    public function exists(): bool
    {
        return $this->pair !== null;
    }

    public function hasConnected(): bool
    {
        return $this->pair['has_connected'] ?? false;
    }

    public function originalTeamIds(): array
    {
        return [
            $this->pair['player_1_original_team_id'],
            $this->pair['player_2_original_team_id'],
        ];
    }

    public function ally(): ?Player
    {
        if (! $this->pair) {
            return null;
        }

        $other_id = $this->pair['player_1_id'] === $this->player_id
            ? $this->pair['player_2_id']
            : $this->pair['player_1_id'];

        return Player::find($other_id);
    }

    public function raw(): ?array
    {
        return $this->pair;
    }
}
