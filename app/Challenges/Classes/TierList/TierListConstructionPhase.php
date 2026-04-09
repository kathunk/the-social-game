<?php

namespace App\Challenges\Classes\TierList;

use App\Challenges\Classes\BaseChallengeClass;
use App\Events\GameUpdatedForReverb;
use App\Events\TierList\TierListSubmitted;
use App\Models\Player;
use App\Modifiers\Classes\TierList\TierListModifier;
use App\States\GameState;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Thunk\Verbs\Facades\Verbs;

class TierListConstructionPhase extends BaseChallengeClass
{
    const NAME = 'Build 3 tier lists';

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
        array $team_names,
    ) {
        if (! in_array(TierListModifier::key(), $modifier_keys)) {
            return 'Tier list modifier is required to run this challenge';
        }

        return false;
    }

    public function dataArrayForState(): array
    {
        $previously_played_categories = $this->challenge->game->players
            ->map(
                fn ($player) => $player->user->games
                    ->filter(
                        fn ($game) => $game->gameMode &&
                            in_array(
                                self::key(),
                                $game->challenges
                                    ->pluck('class_key')
                                    ->toArray(),
                            ),
                    )
                    ?->pluck('challenge_data.categories')
                    ->flatten()
                    ->toArray() ?? [],
            )
            ->flatten()
            ->toArray();

        $category_usage_counts = collect($previously_played_categories)
            ->countBy()
            ->toArray();

        $all_categories = [
            'animal',
            'app',
            'beverage',
            'body_part',
            'book',
            'candy',
            'celebrity',
            'chain_restaurant',
            'chore',
            'city',
            'college_major',
            'color',
            'date_idea',
            'feeling',
            'fictional_character',
            'game',
            'genre',
            'gift',
            'greeting',
            'guilty_pleasure',
            'halloween_costume',
            'historical_figure',
            'hobby',
            'holiday',
            'job_title',
            'kitchen_utensil',
            'life_skill',
            'mammal',
            'month',
            'movie',
            'musical_artist',
            'musical_instrument',
            'names',
            'nickname',
            'politician',
            'school_subject',
            'smell',
            'snack',
            'song',
            'sound',
            'spherical_object',
            'sport',
            'superpower',
            'texture',
            'tv_show',
            'vacation_destination',
            'vegetable',
            'vehicle',
            'villain',
            'weapon',
            'website',
        ];

        $categories = collect($all_categories)
            ->shuffle()
            ->sortBy(fn ($category) => $category_usage_counts[$category] ?? 0)
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

        $next_category = collect($all_categories)
            ->filter(fn ($cat) => ! in_array($cat, $submitted))
            ->first();

        $formatted = Str::singular(Str::replace('_', ' ', $next_category));

        $a_placeholder = collect([
            'Glorious A-tier '.$formatted,
            'Phenomenal A-tier '.$formatted,
            'The greatest A-tier '.$formatted,
            'The undisputed best A-tier '.$formatted,
            'Perfect A-tier '.$formatted,
            'Legendary A-tier '.$formatted,
            'Sublime A-tier '.$formatted,
            'Unmatched A-tier '.$formatted,
            'Elite A-tier '.$formatted,
        ])->random();

        $b_placeholder = collect([
            'Good enough B-tier '.$formatted,
            'Solid B-tier '.$formatted,
            'Pretty good B-tier '.$formatted,
            'Reliable B-tier '.$formatted,
            'Respectable B-tier '.$formatted,
            'Decent B-tier '.$formatted,
            'Not bad B-tier '.$formatted,
            'Worthy B-tier '.$formatted,
            'Steady B-tier '.$formatted,
        ])->random();

        $c_placeholder = collect([
            'Mediocre C-tier '.$formatted,
            '"Meh" C-tier '.$formatted,
            'Okay C-tier '.$formatted,
            'So-so C-tier '.$formatted,
            'Forgettable C-tier '.$formatted,
            'Average C-tier '.$formatted,
            'Unremarkable C-tier '.$formatted,
            'Plain C-tier '.$formatted,
            'Serviceable C-tier '.$formatted,
        ])->random();

        $d_placeholder = collect([
            'Bad D-tier '.$formatted,
            'Disappointing D-tier '.$formatted,
            'D-tier '.$formatted,
            'Sad D-tier '.$formatted,
            'Weak D-tier '.$formatted,
            'Regrettable D-tier '.$formatted,
            'Lowly D-tier '.$formatted,
            'Sorry D-tier '.$formatted,
        ])->random();

        $f_placeholder = collect([
            'Terrible horrible F-tier '.$formatted,
            'No good very bad F-tier '.$formatted,
            'Absolute worst F-tier '.$formatted,
            'Abysmal F-tier '.$formatted,
            'Horrible F-tier '.$formatted,
            'Terrible F-tier '.$formatted,
            'Awful F-tier '.$formatted,
            'Dumpster fire F-tier '.$formatted,
            'Nightmare F-tier '.$formatted,
            'Rock bottom F-tier '.$formatted,
        ])->random();

        return $this->form()
            ->title(self::NAME)
            ->when(
                $next_category === null,
                fn ($form) => $form->subtitle(
                    'Waiting for other players to submit their lists...',
                ),
            )
            ->when(
                $next_category !== null,
                fn ($form) => $form
                    ->subtitle(self::DESCRIPTION)
                    ->divider()
                    ->title(
                        Str::title(
                            Str::plural(Str::replace('_', ' ', $next_category)),
                        ),
                    )
                    ->input(
                        property_name: $next_category.'-A',
                        validation_rules: 'required|string|min:1|max:100',
                        validation_messages: [
                            'required' => 'Submisions are required',
                            'max' => 'Submissions must be less than 100 characters',
                        ],
                        placeholder: $a_placeholder,
                    )
                    ->input(
                        property_name: $next_category.'-B',
                        validation_rules: 'required|string|min:1|max:100',
                        validation_messages: [
                            'required' => 'Submisions are required',
                            'max' => 'Submissions must be less than 100 characters',
                        ],
                        placeholder: $b_placeholder,
                    )
                    ->input(
                        property_name: $next_category.'-C',
                        validation_rules: 'required|string|min:1|max:100',
                        validation_messages: [
                            'required' => 'Submisions are required',
                            'max' => 'Submissions must be less than 100 characters',
                        ],
                        placeholder: $c_placeholder,
                    )
                    ->input(
                        property_name: $next_category.'-D',
                        validation_rules: 'required|string|min:1|max:100',
                        validation_messages: [
                            'required' => 'Submisions are required',
                            'max' => 'Submissions must be less than 100 characters',
                        ],
                        placeholder: $d_placeholder,
                    )
                    ->input(
                        property_name: $next_category.'-F',
                        validation_rules: 'required|string|min:1|max:100',
                        validation_messages: [
                            'required' => 'Submisions are required',
                            'max' => 'Submissions must be less than 100 characters',
                        ],
                        placeholder: $f_placeholder,
                    )
                    ->buttonGroup()
                    ->button(
                        label: 'Submit',
                        action: 'submitTierList',
                        properties_to_validate: [
                            $next_category.'-A',
                            $next_category.'-B',
                            $next_category.'-C',
                            $next_category.'-D',
                            $next_category.'-F',
                        ],
                    )
                    ->endGroup(),
            )
            ->build();
    }

    public function modifier()
    {
        return $this->challenge->game->modifiers->firstWhere(
            'class_key',
            TierListModifier::key(),
        );
    }

    public function categoriesSubmitted(Player $player): array
    {
        return collect($this->modifier()->modifier_data['submissions'])
            ->filter(
                fn ($submission) => $submission['player_id'] === $player->id,
            )
            ->pluck('category')
            ->unique()
            ->toArray();
    }

    public function submitTierList(Player $player, array $params)
    {
        $mapped = collect($params)
            ->map(
                fn ($entry, $key) => [
                    'value' => $entry,
                    'category' => Str::before($key, '-'),
                    'tier' => Str::afterLast($key, '-'),
                    'player_id' => $player->id,
                ],
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

        if (
            count(
                $this->challenge->fresh()->challenge_data['has_submitted'],
            ) === $player->game->players->count()
        ) {
            $this->challenge->fresh()->end();
            Verbs::commit();
            $this->challenge->fresh()->next()->start();

            event(new GameUpdatedForReverb($player->game->fresh()));
        }

        return redirect()->route('game-dashboard', ['game' => $player->game]);
    }

    public function onChallengeEnded(GameState $game_state)
    {
        try {
            $players = $game_state->players();
            $categories = $this->challenge_state->challenge_data['categories'];
            $modifier = $game_state
                ->modifiers()
                ->firstWhere('class_key', TierListModifier::key());
            $submissions = $modifier->modifier_data['submissions'];
            $answer_keys = $modifier->modifier_data['answer_keys'];

            $categories_used = collect($categories)
                ->mapWithKeys(fn ($category) => [$category => 0])
                ->toArray();

            $playerIds = collect($players)->pluck('id')->unique()->all();
            $playerNames = collect($players)
                ->mapWithKeys(fn ($p) => [$p->id => $p->name])
                ->all();

            foreach ($players as $player) {
                $random_least_used_category = collect($categories)
                    ->shuffle()
                    ->sortBy(fn ($category) => $categories_used[$category])
                    ->first();
                $categories_used[$random_least_used_category]++;
                $answer_keys['single_category'][$player->id][
                    'category'
                ] = $random_least_used_category;
            }

            $round1 = $this->generatePlayerAssignedOpponents($playerIds);
            $round2 = $this->generatePlayerAssignedOpponents(
                $playerIds,
                $round1,
            );
            if (count($playerIds) === 2) {
                $round3 = $this->generatePlayerAssignedOpponents(
                    $playerIds,
                    $round2,
                );
            }

            foreach ($playerIds as $id) {
                $answer_keys['single_opponent_round_1'][$id]['opponent'] =
                    $playerNames[$round1[$id]];
                $answer_keys['single_opponent_round_2'][$id]['opponent'] =
                    $playerNames[$round2[$id]];
                if (count($playerIds) === 2) {
                    $answer_keys['single_opponent_round_3'][$id]['opponent'] =
                        $playerNames[$round3[$id]];
                }
            }

            // Round 3 - distribute submissions to opponents
            // Assign per-tier using a derangement across players to guarantee feasibility
            if (count($playerIds) > 2) {
                foreach (['A', 'B', 'C', 'D', 'F'] as $tier) {
                    // Map each sender to a different receiver (no self) for this tier
                    $mapping = $this->generatePlayerAssignedOpponents(
                        $playerIds,
                    );

                    foreach ($mapping as $senderId => $receiverId) {
                        $receiver_category =
                            $answer_keys['single_category'][$receiverId][
                                'category'
                            ];

                        $viable = collect($submissions)->first(function (
                            $s,
                        ) use ($senderId, $receiver_category, $tier) {
                            return $s['player_id'] === $senderId &&
                                $s['category'] === $receiver_category &&
                                $s['tier'] === $tier;
                        });

                        if (! $viable) {
                            throw new \RuntimeException(
                                "Round 3 allocation error: missing {$tier} for {$receiver_category} from sender {$playerNames[$senderId]}",
                            );
                        }

                        // Assign and remove from pool
                        $answer_keys['single_category'][$receiverId][
                            $tier
                        ] = $viable;

                        $submissions = collect($submissions)
                            ->reject(function ($s) use (
                                $senderId,
                                $receiver_category,
                                $tier,
                            ) {
                                return $s['player_id'] === $senderId &&
                                    $s['category'] === $receiver_category &&
                                    $s['tier'] === $tier;
                            })
                            ->toArray();
                    }
                }

                // this is for debugging
                if (count($submissions) !== $players->count() * 10) {
                    throw new \Exception(
                        'There are '.
                            count($submissions).
                            ' submissions remaining, but there should be '.
                            $players->count() * 10,
                    );
                }

                $players->each(function ($player) use ($submissions) {
                    $all_submissions = collect($submissions)->filter(
                        fn ($submission) => $submission['player_id'] ===
                            $player->id,
                    );
                    $a_tiers = $all_submissions
                        ->filter(fn ($submission) => $submission['tier'] === 'A')
                        ->count();
                    $b_tiers = $all_submissions
                        ->filter(fn ($submission) => $submission['tier'] === 'B')
                        ->count();
                    $c_tiers = $all_submissions
                        ->filter(fn ($submission) => $submission['tier'] === 'C')
                        ->count();
                    $d_tiers = $all_submissions
                        ->filter(fn ($submission) => $submission['tier'] === 'D')
                        ->count();
                    $f_tiers = $all_submissions
                        ->filter(fn ($submission) => $submission['tier'] === 'F')
                        ->count();

                    if (
                        $a_tiers !== 2 ||
                        $b_tiers !== 2 ||
                        $c_tiers !== 2 ||
                        $d_tiers !== 2 ||
                        $f_tiers !== 2
                    ) {
                        throw new \Exception(
                            'Player '.
                                $player->name.
                                ' has '.
                                $a_tiers.
                                ' A tiers, '.
                                $b_tiers.
                                ' B tiers, '.
                                $c_tiers.
                                ' C tiers, '.
                                $d_tiers.
                                ' D tiers, and '.
                                $f_tiers.
                                ' F tiers',
                        );
                    }
                });
            }

            $round1_inverse = [];
            $round2_inverse = [];
            $round3_inverse = [];
            foreach ($round1 as $guesser => $opponent) {
                $round1_inverse[$opponent] = $guesser;
            }
            foreach ($round2 as $guesser => $opponent) {
                $round2_inverse[$opponent] = $guesser;
            }
            if (count($playerIds) === 2) {
                foreach ($round3 as $guesser => $opponent) {
                    $round3_inverse[$opponent] = $guesser;
                }
            }

            foreach ($playerIds as $id) {
                $answer_keys['single_opponent_round_1'][$id]['opponent'] =
                    $playerNames[$round1[$id]];
                $answer_keys['single_opponent_round_2'][$id]['opponent'] =
                    $playerNames[$round2[$id]];
            }

            // for opponent-specific rounds, distribute submissions to assigned opponents deterministically
            foreach ($players as $player) {
                $receiver_1_id = $round1_inverse[$player->id];
                $receiver_2_id = $round2_inverse[$player->id];
                if (count($playerIds) === 2) {
                    $receiver_3_id = $round3_inverse[$player->id];
                }

                // Group remaining submissions by tier for this player
                $player_submissions_by_tier = collect($submissions)
                    ->filter(
                        fn ($submission) => $submission['player_id'] ===
                            $player->id,
                    )
                    ->shuffle()
                    ->groupBy('tier');

                foreach (['A', 'B', 'C', 'D', 'F'] as $tier) {
                    $items = $player_submissions_by_tier
                        ->get($tier, collect())
                        ->values();

                    // First goes to the player assigned to guess this player in round 1, second in round 2
                    $answer_keys['single_opponent_round_1'][$receiver_1_id][
                        $tier
                    ] = $items[0];
                    $answer_keys['single_opponent_round_2'][$receiver_2_id][
                        $tier
                    ] = $items[1];
                    if (count($playerIds) === 2) {
                        $answer_keys['single_opponent_round_3'][$receiver_3_id][
                            $tier
                        ] = $items[2];
                    }

                    $items_to_remove = [$items[0], $items[1]];
                    if (count($playerIds) === 2) {
                        $items_to_remove[] = $items[2];
                    }

                    // Remove them from the pool
                    foreach ($items_to_remove as $used) {
                        $submissions = collect($submissions)
                            ->reject(
                                fn ($sub) => $sub['player_id'] === $player->id &&
                                    $sub['tier'] === $used['tier'] &&
                                    $sub['category'] === $used['category'],
                            )
                            ->toArray();
                    }
                }

                // Defensive cleanup; should already be empty for this player
                $submissions = collect($submissions)
                    ->reject(
                        fn ($submission) => $submission['player_id'] ===
                            $player->id,
                    )
                    ->toArray();
            }

            // this is for debugging
            if (count($submissions) !== 0) {
                throw new \Exception(
                    'There are '.
                        count($submissions).
                        ' submissions remaining, but there should be 0',
                );
            }

            $game_state
                ->modifiers()
                ->firstWhere(
                    'class_key',
                    TierListModifier::key(),
                )->modifier_data['answer_keys'] = $answer_keys;
        } catch (\Exception $e) {
            Log::error('Error in onChallengeEnded', [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);
            throw $e;
        }
    }

    public function generatePlayerAssignedOpponents(
        array $playerIds,
        array $forbiddenPairs = [],
    ): array {
        $maxAttempts = 100;

        if (collect($playerIds)->unique()->count() === 2) {
            $player_1 = collect($playerIds)->first();
            $player_2 = collect($playerIds)->last();

            return [
                $player_1 => $player_2,
                $player_2 => $player_1,
            ];
        }

        for ($i = 0; $i < $maxAttempts; $i++) {
            $shuffled = $playerIds;
            shuffle($shuffled);

            $mapping = array_combine($playerIds, $shuffled);

            $valid = true;
            foreach ($mapping as $from => $to) {
                if (
                    $from === $to ||
                    (isset($forbiddenPairs[$from]) &&
                        $forbiddenPairs[$from] === $to)
                ) {
                    $valid = false;
                    break;
                }
            }

            if ($valid) {
                return $mapping;
            }
        }

        throw new \Exception(
            "Could not generate valid derangement after {$maxAttempts} tries",
        );
    }
}
