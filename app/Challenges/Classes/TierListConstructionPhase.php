<?php

namespace App\Challenges\Classes;

use App\Models\Player;
use App\States\GameState;
use Illuminate\Support\Str;
use Thunk\Verbs\Facades\Verbs;
use App\Events\TierListSubmitted;
use App\Modifiers\Classes\TierListModifier;

class TierListConstructionPhase extends BaseChallengeClass
{
    const NAME = 'Build your tier lists';

    const DESCRIPTION = 'Add a submission for each tier in each category below.';

    const TYPE = 'individual';

    public static function key(): string
    {
        return 'tier_list_construction_phase';
    }

    public function isInvalidForTemplate(
        array $challenge_keys,
        array $modifier_keys,
        string $type,
        array $team_names
    ) {
        if (! in_array(TierListModifier::key(), $modifier_keys)) {
            return 'Tier list modifier is required to run this challenge';
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        // @todo prioritize categories that have been used less often
        // $previously_played_categories = $this->game->players

        $categories = collect([
            'candy',
            'city',
            'fictional_character',
            'movie',
            'school_subject',
            'tv_show',
        ])
        ->shuffle()
        ->take(3)
        ->toArray();

        return [
            'categories' => $categories,
            'has_submitted' => [],
        ];
    }

    public function frontendComponent(Player $player): array
    {
        $submitted = $this->categoriesSubmitted($player);
        $all_categories = $this->challenge->challenge_data['categories'];

        $next_category = collect($all_categories)->filter(fn($cat) => ! in_array($cat, $submitted))->first();

        return $this->form()
            ->title(self::NAME)
            ->when($next_category === null, fn ($form) => 
                $form
                ->subtitle("Waiting for other players to submit their lists...")
            )
            ->when($next_category !== null, fn ($form) => 
                $form
                ->subtitle(self::DESCRIPTION)
                ->divider()
                ->title(Str::title(Str::plural(Str::replace('_', ' ', $next_category))))
                ->input(
                    property_name: $next_category . '-A',
                    validation_rules: 'required|string|min:1',
                    validation_messages: ['required' => 'Submisions are required'],
                    placeholder: 'A tier ' . Str::singular(Str::replace('_', ' ', $next_category)),
                )
                ->input(
                    property_name: $next_category . '-B',
                    validation_rules: 'required|string|min:1',
                    validation_messages: ['required' => 'Submisions are required'],
                    placeholder: 'B tier ' . Str::singular(Str::replace('_', ' ', $next_category)),
                )
                ->input(
                    property_name: $next_category . '-C',
                    validation_rules: 'required|string|min:1',
                    validation_messages: ['required' => 'Submisions are required'],
                    placeholder: 'C tier ' . Str::singular(Str::replace('_', ' ', $next_category)),
                )
                ->input(
                    property_name: $next_category . '-D',
                    validation_rules: 'required|string|min:1',
                    validation_messages: ['required' => 'Submisions are required'],
                    placeholder: 'D tier ' . Str::singular(Str::replace('_', ' ', $next_category)),
                )
                ->input(
                    property_name: $next_category . '-F',
                    validation_rules: 'required|string|min:1',
                    validation_messages: ['required' => 'Submisions are required'],
                    placeholder: 'F tier ' . Str::singular(Str::replace('_', ' ', $next_category)),
                )
                ->buttonGroup()
                ->button(
                    label: 'Submit',
                    action: 'submitTierList',
                    properties_to_validate: [$next_category . '-A', $next_category . '-B', $next_category . '-C', $next_category . '-D', $next_category . '-F'],
                )
                ->endGroup()
            )
            ->build();
    }

    public function modifier()
    {
        return $this->challenge->game->modifiers->firstWhere('class_key', TierListModifier::key());
    }

    public function categoriesSubmitted(Player $player): array
    {
        return collect($this->modifier()->modifier_data['submissions'])
            ->filter(fn($submission) => $submission['player_id'] === $player->id)
            ->pluck('category')
            ->unique()
            ->toArray();
    }

    public function submitTierList(Player $player, array $params)
    {
        $mapped = collect($params)->map(fn($entry, $key) =>
            [
                'value' => $entry,
                'category' => Str::before($key, '-'),
                'tier' => Str::afterLast($key, '-'),
                'player_id' => $player->id, 
            ]
        )
        ->values()
        ->toArray();

        TierListSubmitted::fire(
            player_id: $player->id,
            challenge_id: $this->challenge->id,
            modifier_id: $this->modifier()->id,
            game_id: $this->challenge->game_id,
            submissions: $mapped,
        );

        Verbs::commit();
        
        if (count($this->challenge->fresh()->challenge_data['has_submitted']) === $player->game->players->count()) {
            // @todo uncomment this after testing. 
            // $this->challenge->fresh()->end();
        }

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function onChallengeEnded(GameState $game_state)
    {
        $players = $game_state->players();
        $categories = $this->challenge_state->challenge_data['categories'];
        $modifier = $game_state->modifiers()->firstWhere('class_key', TierListModifier::key());
        $submissions = $modifier->modifier_data['submissions'];
        $answer_keys = $modifier->modifier_data['answer_keys'];

        $categories_used = collect($categories)->mapWithKeys(fn($category) => [$category => 0])->toArray();
        
        $playerIds = collect($players)->pluck('id')->all();
        $playerNames = collect($players)->mapWithKeys(fn($p) => [$p->id => $p->name])->all();

        foreach ($players as $player) {
            $random_least_used_category = collect($categories)
                ->shuffle()
                ->sortBy(fn($category) => $categories_used[$category])
                ->first();
            $categories_used[$random_least_used_category]++;
            $answer_keys['single_category'][$player->id]['category'] = $random_least_used_category;

            // Round 1 assignments (e.g., A → B, B → C, C → A)
            $round1 = $this->generatePlayerAssignedOpponents($playerIds);

            // Round 2 assignments with no repeated pairs
            $round2 = $this->generatePlayerAssignedOpponents($playerIds, $round1);

            // Assign the opponents
            foreach ($playerIds as $id) {
                $answer_keys['single_opponent_round_1'][$id]['opponent'] = $playerNames[$round1[$id]];
                $answer_keys['single_opponent_round_2'][$id]['opponent'] = $playerNames[$round2[$id]];
            }
        }            

        // Round 3 - distribute submissions to opponents
        foreach ($players->reverse() as $player) {
            $round_3_need_item_distributed = 0;
            $opponents = $players->filter(fn($p) => $p->id !== $player->id)->values(); 
            $player_submissions = collect($submissions)->filter(function($submission) use ($player) {
                return $submission['player_id'] === $player->id;
            })->shuffle();
            $player_tiers_used = [];
        
            $opponent_index = 0;
        
            while ($round_3_need_item_distributed < 5) {
                $opponent = $opponents[$opponent_index % $opponents->count()];
                $opponent_index++;
        
                $opponent_answer_key = $answer_keys['single_category'][$opponent->id];
                $opponent_category = $opponent_answer_key['category'];
        
                $opponent_round_3_empty_slots = collect($opponent_answer_key)
                    ->except(['category']) 
                    ->filter(fn($tier) => $tier === null)
                    ->keys();
        
                if ($opponent_round_3_empty_slots->isEmpty()) {
                    continue;
                }

                $viable_submission = $player_submissions
                    ->filter(fn($submission) =>
                        $submission['category'] === $opponent_category &&
                        $opponent_round_3_empty_slots->contains($submission['tier']) &&
                        !in_array($submission['tier'], $player_tiers_used)
                    )
                    ->first();

                if ($viable_submission === null) {
                    continue;
                }
        
                $answer_keys['single_category'][$opponent->id][$viable_submission['tier']] = $viable_submission;
                $player_tiers_used[] = $viable_submission['tier'];
                $round_3_need_item_distributed++;

                $submissions = collect($submissions)->reject(function($submission) use ($player, $opponent_category, $viable_submission) { 
                    return $submission['player_id'] === $player->id 
                        && $submission['tier'] === $viable_submission['tier']
                        && $submission['category'] === $opponent_category;
                })->toArray();
            }
        }

        // this is for debugging
        if (count($submissions) !== $players->count() * 10) {
            throw new \Exception('There are ' . count($submissions) . ' submissions remaining, but there should be ' . $players->count() * 10);
        }
        $players->each(function($player) use ($submissions) {
            $all_submissions = collect($submissions)->filter(fn($submission) => $submission['player_id'] === $player->id);
            $a_tiers = $all_submissions->filter(fn($submission) => $submission['tier'] === 'A')->count();
            $b_tiers = $all_submissions->filter(fn($submission) => $submission['tier'] === 'B')->count();
            $c_tiers = $all_submissions->filter(fn($submission) => $submission['tier'] === 'C')->count();
            $d_tiers = $all_submissions->filter(fn($submission) => $submission['tier'] === 'D')->count();
            $f_tiers = $all_submissions->filter(fn($submission) => $submission['tier'] === 'F')->count();

            if ($a_tiers !== 2 || $b_tiers !== 2 || $c_tiers !== 2 || $d_tiers !== 2 || $f_tiers !== 2) {
                throw new \Exception('Player ' . $player->name . ' has ' . $a_tiers . ' A tiers, ' . $b_tiers . ' B tiers, ' . $c_tiers . ' C tiers, ' . $d_tiers . ' D tiers, and ' . $f_tiers . ' F tiers');
            }
        });

        // rounds 1 and 2 - distribute submissions to opponents
        foreach ($players as $player) {
            // @todo what in the hell why are the names sometimes wrong.
            $buddy_1_name = $answer_keys['single_opponent_round_1'][$player->id]['opponent'];
            $buddy_1_id = $players->firstWhere('name', $buddy_1_name)->id;
            $buddy_2_name = $answer_keys['single_opponent_round_2'][$player->id]['opponent'];
            $buddy_2_id = $players->firstWhere('name', $buddy_2_name)->id;

            $player_submissions = collect($submissions)
                ->shuffle()
                ->filter(fn($submission) => $submission['player_id'] === $player->id)
                ->sortBy(fn($submission) => $submission['tier']);

            foreach($player_submissions as $submission) {
                $opponent_1_empty_slots = collect($answer_keys['single_opponent_round_1'][$buddy_1_id])->filter(fn($slot) => $slot === null)->count();
                $opponent_2_empty_slots = collect($answer_keys['single_opponent_round_2'][$buddy_2_id])->filter(fn($slot) => $slot === null)->count();

                if ($opponent_1_empty_slots === $opponent_2_empty_slots) {
                    $answer_keys['single_opponent_round_1'][$buddy_1_id][$submission['tier']] = $submission;
                } else {
                    $answer_keys['single_opponent_round_2'][$buddy_2_id][$submission['tier']] = $submission;
                }

                $submissions = collect($submissions)->reject(fn($sub) =>
                    $sub['player_id'] === $player->id 
                    && $sub['tier'] === $submission['tier']
                    && $sub['category'] === $submission['category']
                )
                ->toArray();
            }

            $submissions = collect($submissions)->reject(function($submission) use ($player) {
                return $submission['player_id'] === $player->id;
            })->toArray();
        }

        // this is for debugging
        if (count($submissions) !== 0) {
            throw new \Exception('There are ' . count($submissions) . ' submissions remaining, but there should be 0');
        }
				
        $game_state->modifiers()->firstWhere('class_key', TierListModifier::key())->modifier_data['answer_keys'] = $answer_keys;
    }

    function generatePlayerAssignedOpponents(array $playerIds, array $forbiddenPairs = []): array 
    {
        $maxAttempts = 100;
    
        for ($i = 0; $i < $maxAttempts; $i++) {
            $shuffled = $playerIds;
            shuffle($shuffled);
    
            $mapping = array_combine($playerIds, $shuffled);
    
            $valid = true;
            foreach ($mapping as $from => $to) {
                if (
                    $from === $to ||
                    (isset($forbiddenPairs[$from]) && $forbiddenPairs[$from] === $to)
                ) {
                    $valid = false;
                    break;
                }
            }
    
            if ($valid) {
                return $mapping;
            }
        }
    
        throw new \Exception("Could not generate valid derangement after {$maxAttempts} tries");
    }
}
