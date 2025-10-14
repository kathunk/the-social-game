<?php

namespace App\Modifiers\Classes;

use App\Models\Game;
use App\Models\Team;
use App\Models\Player;
use App\Events\TeamCreated;
use Thunk\Verbs\Facades\Verbs;
use App\Events\PlayerJoinedTeam;
use Illuminate\Support\Collection;
use App\Events\PlayerSpawnedInFarm;
use App\Events\PlayerJoinedFarmTeam;
use App\Events\PlayerBootedFromFarmTeam;
use App\Events\PlayerRequestedToJoinFarmTeam;
use App\Events\PlayerPromotedToTeamLeaderInFarm;
use App\Events\PlayerCanceledRequestToJoinFarmTeam;
use App\Events\TeamLeaderDeclineRequestToJoinFarmTeam;
use App\Events\TeamLeaderAcceptedRequestToJoinFarmTeam;
use App\Events\PlayerRedistributedResourcesToNewFarmTeam;

class FarmTeams extends BaseModifierClass
{
    const NAME = 'Farm Teams';

    const DESCRIPTION = 'The teams for the farm game.';

    const TYPE = 'team';

    public static function key(): string
    {
        return 'farm_teams';
    }

    public function isInvalidForTemplate(
        array $challenges,
        array $modifiers,
        string $type,
        array $team_names
    ) {
        if (! in_array(FarmMap::key(), $modifiers)) {
            return 'Farm map modifier is required to run this modifier';
        }

        return false;
    }

    public function dataArrayForState(?Game $game = null): array
    {
        return [
            'leaders' => [
            //     'team_id' => 'player_id',
            ],
            'requests' => [
                // 'player_id' => 'team_id',
            ]
        ];
    }

    public function frontendComponent(Player $player): array
    {
        if ($player->team_id === null) {
            return $this->form()
                ->title('Create team')
                ->subtitle('You are a lone wolf. Create a team to continue.')
                ->select(
                    label: 'Adjective',
                    property_name: 'adjective',
                    options: [
                        'Adamant' => 'Adamant', 'Adroit' => 'Adroit', 'Amatory' => 'Amatory', 'Animistic' => 'Animistic', 'Antic' => 'Antic', 'Arcadian' => 'Arcadian', 'Baleful' => 'Baleful', 'Bellicose' => 'Bellicose', 'Bilious' => 'Bilious', 'Boorish' => 'Boorish', 'Calamitous' => 'Calamitous', 'Caustic' => 'Caustic', 'Cerulean' => 'Cerulean', 'Comely' => 'Comely', 'Concomitant' => 'Concomitant', 'Contumacious' => 'Contumacious', 'Corpulent' => 'Corpulent', 'Crapulous' => 'Crapulous', 'Defamatory' => 'Defamatory', 'Didactic' => 'Didactic', 'Dilatory' => 'Dilatory', 'Dowdy' => 'Dowdy', 'Efficacious' => 'Efficacious', 'Effulgent' => 'Effulgent', 'Egregious' => 'Egregious', 'Endemic' => 'Endemic', 'Equanimous' => 'Equanimous', 'Execrable' => 'Execrable', 'Fastidious' => 'Fastidious', 'Feckless' => 'Feckless', 'Fecund' => 'Fecund', 'Friable' => 'Friable', 'Fulsome' => 'Fulsome', 'Garrulous' => 'Garrulous', 'Guileless' => 'Guileless', 'Gustatory' => 'Gustatory', 'Heuristic' => 'Heuristic', 'Histrionic' => 'Histrionic', 'Hubristic' => 'Hubristic', 'Incendiary' => 'Incendiary', 'Insidious' => 'Insidious', 'Insolent' => 'Insolent', 'Intransigent' => 'Intransigent', 'Inveterate' => 'Inveterate', 'Invidious' => 'Invidious', 'Irksome' => 'Irksome', 'Jejune' => 'Jejune', 'Jocular' => 'Jocular', 'Judicious' => 'Judicious', 'Lachrymose' => 'Lachrymose', 'Limpid' => 'Limpid', 'Loquacious' => 'Loquacious', 'Luminous' => 'Luminous', 'Mannered' => 'Mannered', 'Mendacious' => 'Mendacious', 'Meretricious' => 'Meretricious', 'Minatory' => 'Minatory', 'Mordant' => 'Mordant', 'Munificent' => 'Munificent', 'Nefarious' => 'Nefarious', 'Noxious' => 'Noxious', 'Obtuse' => 'Obtuse', 'Parsimonious' => 'Parsimonious', 'Pendulous' => 'Pendulous', 'Pernicious' => 'Pernicious', 'Pervasive' => 'Pervasive', 'Petulant' => 'Petulant', 'Platitudinous' => 'Platitudinous', 'Precipitate' => 'Precipitate', 'Propitious' => 'Propitious', 'Puckish' => 'Puckish', 'Querulous' => 'Querulous', 'Quiescent' => 'Quiescent', 'Rebarbative' => 'Rebarbative', 'Recalcitrant' => 'Recalcitrant', 'Redolent' => 'Redolent', 'Rhadamanthine' => 'Rhadamanthine', 'Risible' => 'Risible', 'Ruminative' => 'Ruminative', 'Sagacious' => 'Sagacious', 'Salubrious' => 'Salubrious', 'Sartorial' => 'Sartorial', 'Sclerotic' => 'Sclerotic', 'Serpentine' => 'Serpentine', 'Spasmodic' => 'Spasmodic', 'Strident' => 'Strident', 'Taciturn' => 'Taciturn', 'Tenacious' => 'Tenacious', 'Tremulous' => 'Tremulous', 'Trenchant' => 'Trenchant', 'Turbulent' => 'Turbulent', 'Turgid' => 'Turgid', 'Ubiquitous' => 'Ubiquitous', 'Uxorious' => 'Uxorious', 'Verdant' => 'Verdant', 'Voluble' => 'Voluble', 'Voracious' => 'Voracious', 'Wheedling' => 'Wheedling', 'Withering' => 'Withering', 'Zealous' => 'Zealous',
                    ],
                    placeholder: 'Select an adjective...',
                    validation_rules: 'required|exists:teams,id',
                    validation_messages: [
                        'required' => 'Must select a team',
                        'exists' => 'Must select a valid team',
                    ],
                )
                ->select(
                    label: 'Noun',
                    property_name: 'noun',
                    options: [
                        'Bagpipes' => 'Bagpipes', 'Baboons' => 'Baboons', 'Bananapeels' => 'Bananapeels', 'Beanbags' => 'Beanbags', 'Bellybuttons' => 'Bellybuttons', 'Boomerangs' => 'Boomerangs', 'Burritos' => 'Burritos', 'Cabbagepatches' => 'Cabbagepatches', 'Cantalords' => 'Cantalords', 'Cantaloupes' => 'Cantaloupes', 'Cantankerbots' => 'Cantankerbots', 'Cheeseboards' => 'Cheeseboards', 'Clamdiggers' => 'Clamdiggers', 'Cobwebs' => 'Cobwebs', 'Corkscrews' => 'Corkscrews', 'Crumpets' => 'Crumpets', 'Cucumbers' => 'Cucumbers', 'Cummerbunds' => 'Cummerbunds', 'Cupholders' => 'Cupholders', 'Dingbats' => 'Dingbats', 'Dinguses' => 'Dinguses', 'Doilies' => 'Doilies', 'Doorknobs' => 'Doorknobs', 'Dustbunnies' => 'Dustbunnies', 'Eggplants' => 'Eggplants', 'Ferrets' => 'Ferrets', 'Flipflops' => 'Flipflops', 'Gazebos' => 'Gazebos', 'Gargoyles' => 'Gargoyles', 'Goblets' => 'Goblets', 'Gobstoppers' => 'Gobstoppers', 'Goobers' => 'Goobers', 'Grackles' => 'Grackles', 'Grapefruits' => 'Grapefruits', 'Gumballs' => 'Gumballs', 'Gumdrops' => 'Gumdrops', 'Haversacks' => 'Haversacks', 'Hedgehogs' => 'Hedgehogs', 'Hooligans' => 'Hooligans', 'Jackalopes' => 'Jackalopes', 'Jellybeans' => 'Jellybeans', 'Koozies' => 'Koozies', 'Kumquats' => 'Kumquats', 'Marionettes' => 'Marionettes', 'Marmosets' => 'Marmosets', 'Meatballs' => 'Meatballs', 'Meatloaves' => 'Meatloaves', 'Monocles' => 'Monocles', 'Mopeds' => 'Mopeds', 'Mousetraps' => 'Mousetraps', 'Muffintops' => 'Muffintops', 'Mudpies' => 'Mudpies', 'Nachos' => 'Nachos', 'Napkinrings' => 'Napkinrings', 'Pajamas' => 'Pajamas', 'Pancakelords' => 'Pancakelords', 'Pincushions' => 'Pincushions', 'Plums' => 'Plums', 'Platypuses' => 'Platypuses', 'Plungers' => 'Plungers', 'Pogoers' => 'Pogoers', 'Poptarts' => 'Poptarts', 'Poodles' => 'Poodles', 'Porcupines' => 'Porcupines', 'Poundcakes' => 'Poundcakes', 'Puddleducks' => 'Puddleducks', 'Puffballs' => 'Puffballs', 'Pumperdoodles' => 'Pumperdoodles', 'Pumpernickels' => 'Pumpernickels', 'Pretzels' => 'Pretzels', 'Shenanigans' => 'Shenanigans', 'Shoeboxes' => 'Shoeboxes', 'Shoelaces' => 'Shoelaces', 'Slinkies' => 'Slinkies', 'Skedaddlers' => 'Skedaddlers', 'Skunks' => 'Skunks', 'Snickerdoodles' => 'Snickerdoodles', 'Snorkels' => 'Snorkels', 'Spatulas' => 'Spatulas', 'Sporks' => 'Sporks', 'Sprinklers' => 'Sprinklers', 'Suitcases' => 'Suitcases', 'Taterlings' => 'Taterlings', 'Thumbtacks' => 'Thumbtacks', 'Toadstools' => 'Toadstools', 'Toasters' => 'Toasters', 'Toothbrushes' => 'Toothbrushes', 'Toothpicks' => 'Toothpicks', 'Trampolines' => 'Trampolines', 'Turnips' => 'Turnips', 'Turntables' => 'Turntables', 'Turtlenecks' => 'Turtlenecks', 'Typewriters' => 'Typewriters', 'Wafflebirds' => 'Wafflebirds', 'Wafflecones' => 'Wafflecones', 'Wafflesticks' => 'Wafflesticks', 'Weaselsnouts' => 'Weaselsnouts', 'Weenies' => 'Weenies', 'Whirligigs' => 'Whirligigs', 'Wombats' => 'Wombats', 'Yoyos' => 'Yoyos', 'Zucchinis' => 'Zucchinis',
                    ],
                    placeholder: 'Select a noun...',
                    validation_rules: 'required|exists:teams,id',
                    validation_messages: [
                        'required' => 'Must select a team',
                        'exists' => 'Must select a valid team',
                    ],
                )
                ->buttonGroup()
                ->button('Create team', 'createTeam')
                ->endGroup()
                ->build();
        }
        
        $form = $this->form()->title($player->team->name);

        if ($this->rivalLeadersOnSpace($player)->isNotEmpty()) {
            $form
                ->subtitle("A rival leader is on your space. You may request to join their team")
                ->divider();
        }

        if ($this->outstandingRequesters($player)->isNotEmpty()) {
            $form
                ->subtitle("Players are requesting to join your team.")
                ->divider();
        }

        if ($this->subjectsOnSpace($player)->isNotEmpty()) {
            $form
                ->subtitle("Teammates are on your space. You may boot them from your team.")
                ->divider();
        }

        return $form
            ->buttonGroup()
            ->button("Manage team", "seeTeams")
            ->endGroup()
            ->build();
    }

    public function frontendComponentForDedicatedPage(Player $player): array
    {
        if ($player->team_id === null) {
            return $this->form()->subtitle('You are a lone wolf. Return to the game dashboard to create a new team.')->build();
        }

        $form = $this->form()->title($player->team->name);

        $rival_leaders_on_space = $this->rivalLeadersOnSpace($player);
        $subjects_on_space = $this->subjectsOnSpace($player);
        $outstanding_requesters = $this->outstandingRequesters($player);
        $existing_request = $this->existingRequest($player);
        $current_team = $player->team;
        $other_players_on_team = $current_team->players->filter(fn ($p) => $p->id !== $player->id);

        if ($outstanding_requesters->isNotEmpty()) {
            $form->divider()->subtitle("Players are requesting to join your team. Approve or decline them below.")
            ->select(
                label: 'Select a player',
                property_name: 'player_id',
                options: $outstanding_requesters->mapWithKeys(fn ($requester) => [$requester->id => $requester->name])->toArray(),
                placeholder: 'Select a player...',
                validation_rules: 'required|in:' . implode(',', $outstanding_requesters->pluck('id')->toArray()),
                validation_messages: [
                    'required' => 'Must select a player',
                    'in' => 'Must select a valid player',
                ],
            )
            ->buttonGroup()
                ->button("Accept", "acceptRequestToJoin")
                ->button("Decline", "declineRequestToJoin")
            ->endGroup();
        }

        if ($rival_leaders_on_space->isNotEmpty() && ! $existing_request) {
            $form->divider()->subtitle("A rival leader is on your space. You may request to join their team.")
                ->subtitle("If they accept, you will switch teams and your Grain in your possession will count towards your new team.")
                ->when($other_players_on_team->isNotEmpty(), function ($form) {
                    $form->subtitle("Grain in your current team's silos will remain with them.");
                })
                ->when($other_players_on_team->isEmpty(), function ($form) {
                    $form->subtitle("Since you are the last member of your current team, ownership of your Silos, Fields, and Roads will transfer to the new team as well.");
                })
                ->select(
                    label: 'Select a team',
                    property_name: 'team_id',
                    options: $rival_leaders_on_space->mapWithKeys(fn ($rival_leader) => [$rival_leader->team_id => $rival_leader->team->name . ' (score: ' . $rival_leader->team->score . ')'])->toArray(),
                    placeholder: 'Select a team...',
                    validation_rules: 'required|exists:teams,id',
                    validation_messages: [
                        'required' => 'Must select a team',
                        'exists' => 'Must select a valid team',
                    ],
                )
                ->buttonGroup()
                    ->button("Request to join", "requestToJoin")
                ->endGroup();
        }

        if ($existing_request) {
                $requested_team = Team::find($existing_request);
                $form->divider()->subtitle("You have requested to join " . $requested_team->name . ".")
                    ->buttonGroup()
                        ->button("Cancel request", "cancelRequestToJoin")
                        ->endGroup();
        }

        if ($subjects_on_space->isNotEmpty()) {
            $form->divider()->subtitle("Teammates are on your space. You may boot them from your team.")
                    ->select(
                        label: 'Select a teammate',
                        property_name: 'teammate_id',
                        options: $subjects_on_space->mapWithKeys(fn ($teammate) => [$teammate->id => $teammate->name])->toArray(),
                        placeholder: 'Select a teammate...',
                        validation_rules: 'required|exists:players,id',
                        validation_messages: [
                            'required' => 'Must select a teammate',
                            'exists' => 'Must select a valid teammate',
                        ],
                    )
                ->buttonGroup()
                    ->button("Boot", "bootTeammate")
                ->endGroup();
        }

        return $form->build();
    }

    // data helpers

    public function farmMap()
    {
        if ($this->modifier) {
            return $this->modifier->game->modifiers->firstWhere('class_key', FarmMap::key());
        }

        if ($this->modifier_state) {
            return $this->modifier_state->game()->modifiers()->filter(fn ($modifier) => $modifier->class_key === FarmMap::key())->first();
        }

        return [];
    }

    public function playerSpace(Player $player): array | null
    {
        return collect($this->farmMap()->modifier_data)
            ->filter(fn ($data) => in_array($player->id, $data['player_ids']))->first();
    }

    public function playersOnSpace(Player $player): Collection
    {
        return collect($this->playerSpace($player)['player_ids'])
            ->map(fn ($player_id) => Player::find($player_id));
    }

    public function rivalLeadersOnSpace(Player $player): Collection
    {
        return $this->playersOnSpace($player)
            ->filter(fn ($p) => 
                collect($this->modifier->modifier_data['leaders'])->contains($p->id)
                && $p->team_id !== $player->team_id
            );
    }

    public function playerIsLeader(Player $player): bool
    {
        return collect($this->modifier->modifier_data['leaders'])->contains($player->id);
    }

    public function subjectsOnSpace(Player $player): Collection
    {
        if (! $this->playerIsLeader($player)) {
            return collect();
        }

        return $this->playersOnSpace($player)
            ->filter(fn ($p) => 
                $p->team_id === $player->team_id
                && $p->id !== $player->id
            );
    }

    public function outstandingRequesters(Player $player): Collection
    {
        if (! $this->playerIsLeader($player)) {
            return collect();
        }

        return collect($this->modifier->modifier_data['requests'])
            ->filter(fn ($team_id, $player_id) => $team_id === $player->team_id)
            ->map(fn ($team_id, $player_id) => Player::find($player_id));
    }

    public function existingRequest(Player $player)
    {
        return collect($this->modifier->modifier_data['requests'])
            ->filter(fn ($team_id, $player_id) => $player_id === $player->id)
            ->first();
    }

    public function actionsModifier()
    {
        if ($this->modifier) {
            return $this->modifier->game->modifiers->firstWhere('class_key', FarmActions::key())->modifier_data;
        }

        if ($this->modifier_state) {
            return $this->modifier_state->game()->modifiers()->filter(fn ($modifier) => $modifier->class_key === FarmActions::key())->first()->modifier_data;
        }

        return [];
    }

    public function canAbandonTeam(Player $player): bool
    {
        return $player->team->players->count() > 1;
    }

    // actions

    public function createTeam(Player $player, array $params)
    {
        $existing_team_names = $player->game->teams->pluck('name');

        if ($existing_team_names->contains($params['adjective'] . ' ' . $params['noun'])) {
            throw new \Exception('A team already exists with that name. Try a different combination.');
        }
        
        $team_id = TeamCreated::fire(
            game_id: $player->game_id,
            name: $params['adjective'] . ' ' . $params['noun'],
        )->team_id;

        PlayerPromotedToTeamLeaderInFarm::fire(
            player_id: $player->id,
            team_id: $team_id,
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
        );

        $grain = isset($this->actionsModifier()[$player->id]['grain']) 
            ? $this->actionsModifier()[$player->id]['grain'] 
            : 0;

        PlayerJoinedFarmTeam::fire(
            player_id: $player->id,
            team_id: $team_id,
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            player_grain: $grain,
        );

        if ($this->playerSpace($player) === null) {
            $farm_map = $this->farmMap();
            $random_space = collect($farm_map->modifier_data)->random();

            PlayerSpawnedInFarm::fire(
                player_id: $player->id,
                game_id: $player->game_id,
                map_modifier_id: $farm_map->id,
                x_index: $random_space['x-index'],
                y_index: $random_space['y-index'],
            );
        }

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function seeTeams(Player $player, array $params)
    {
        return redirect()->route("games.mods", [
            "game" => $player->game,
            "modifier" => $this->modifier,
        ]);
    }

    public function requestToJoin(Player $player, array $params)
    {
        PlayerRequestedToJoinFarmTeam::fire(
            player_id: $player->id,
            team_id: (int) $params['team_id'],
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
        );

        Verbs::commit();

        return redirect()->route("games.mods", [
            "game" => $player->game,
            "modifier" => $this->modifier,
        ]);
    }

    public function cancelRequestToJoin(Player $player, array $params)
    {
        PlayerCanceledRequestToJoinFarmTeam::fire(
            player_id: $player->id,
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
        );

        Verbs::commit();

        return redirect()->route("games.mods", [
            "game" => $player->game,
            "modifier" => $this->modifier,
        ]);
    }

    public function declineRequestToJoin(Player $player, array $params)
    {
        TeamLeaderDeclineRequestToJoinFarmTeam::fire(
            player_id: $player->id,
            team_id: $player->team_id,
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            requester_id: (int) $params['player_id'],
        );

        Verbs::commit();

        return redirect()->route("games.mods", [
            "game" => $player->game,
            "modifier" => $this->modifier,
        ]);
    }

    public function acceptRequestToJoin(Player $player, array $params)
    {
        $requester = Player::find((int) $params['player_id']);
        $previous_team_id = $requester->team_id;
        $requester_grain = $this->actionsModifier()[$requester->id]['grain'];
        $requester_team = $requester->team;
        $player_is_last_on_team = $requester_team->players->count() === 1;
        
        TeamLeaderAcceptedRequestToJoinFarmTeam::fire(
            player_id: $player->id,
            team_id: $player->team_id,
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            requester_id: (int) $params['player_id'],
        );
        
        PlayerJoinedFarmTeam::fire(
            player_id: $requester->id,
            previous_team_id: $previous_team_id,
            team_id: $player->team_id,
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            requester_previous_team_id: $requester_team->id,
            player_is_last_on_team: $player_is_last_on_team,
            player_grain: $requester_grain,
        );

        Verbs::commit();

        return redirect()->route("games.mods", [
            "game" => $player->game,
            "modifier" => $this->modifier,
        ]);
    }

    public function bootTeammate(Player $player, array $params)
    {
        $teammate = Player::find((int) $params['teammate_id']);

        PlayerBootedFromFarmTeam::fire(
            player_id: $teammate->id,
            game_id: $player->game_id,
            modifier_id: $this->modifier->id,
            team_id: $player->team_id,
            grain_in_possession: $this->actionsModifier()[$teammate->id]['grain'],
        );

        Verbs::commit();

        return redirect()->route("games.mods", [
            "game" => $player->game,
            "modifier" => $this->modifier,
        ]);
    }
}
