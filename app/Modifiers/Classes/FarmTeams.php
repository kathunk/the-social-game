<?php

namespace App\Modifiers\Classes;

use App\Models\Player;
use App\Events\TeamCreated;
use Thunk\Verbs\Facades\Verbs;
use App\Events\PlayerJoinedTeam;

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

    public function dataArrayForState(): array
    {
        return [];
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
                    placeholder: 'Select an adjective...',
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

        return [];

        // @todo request to join team
        // @todo approve / reject request to join team
        // @todo boot player
        // @todo vote to boot player
    }

    public function createTeam(Player $player, array $params)
    {
        $team_id = TeamCreated::fire(
            game_id: $player->game_id,
            name: $params['adjective'] . ' ' . $params['noun'],
        )->team_id;

        PlayerJoinedTeam::fire(
            player_id: $player->id,
            team_id: $team_id,
            game_id: $player->game_id,
            previous_team_id: $player->team_id,
        );

        Verbs::commit();

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }
}
