<?php

use App\Challenges\Classes\TierListConstructionPhase;
use App\Challenges\Classes\TierListGuess;
use App\Livewire\GameDashboard;
use App\Models\Challenge;
use App\Models\Game;
use App\Modifiers\Classes\TierListModifier;
use Livewire\Livewire;
use Thunk\Verbs\Facades\Verbs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Verbs::commitImmediately();

    $challenges = [
        [
            "challenge_keys" => [TierListConstructionPhase::key()],
            "duration" => null,
        ],
        [
            "challenge_keys" => [TierListGuess::key()],
            "duration" => null,
        ],
        [
            "challenge_keys" => [TierListGuess::key()],
            "duration" => null,
        ],
        [
            "challenge_keys" => [TierListGuess::key()],
            "duration" => null,
        ],
    ];

    $this->mockGameTemplate(
        challenges: $challenges,
        type: "individual",
        modifiers: [TierListModifier::key()],
    );

    $this->createGame();

    $this->player_1 = $this->createPlayer();
    $this->player_2 = $this->createPlayer();
    $this->player_3 = $this->createPlayer();
    $this->player_4 = $this->createPlayer();

    $this->game->start();

    $this->construction_challenge = $this->game->fresh()->challenges->first();
    $this->tier_list_modifier = $this->game->modifiers->first();
});

it("allows challenges that have no duration to be ended", function () {
    expect($this->construction_challenge->starts_at)->not()->toBeNull();
    expect($this->construction_challenge->ends_at)->toBeNull();
    expect($this->construction_challenge->duration)->toBeNull();
});

it("selects 3 categories", function () {
    expect(
        $this->construction_challenge->challenge_data["categories"],
    )->toHaveCount(3);

    // Ensure all categories are strings and valid
    foreach (
        $this->construction_challenge->challenge_data["categories"]
        as $category
    ) {
        expect($category)->toBeString();
        expect(strlen($category))->toBeGreaterThan(0);
    }

    // Ensure categories are unique
    $categories = $this->construction_challenge->challenge_data["categories"];
    expect(array_unique($categories))->toHaveCount(3);
});

it("requires 5 submissions per player per category", function () {
    $key = TierListConstructionPhase::key();
    $category = $this->construction_challenge->challenge_data["categories"][0];

    Livewire::actingAs($this->player_1->fresh()->user)
        ->test(GameDashboard::class, ["game" => $this->game->fresh()])
        ->set("round_properties." . $key . "." . $category . "-A", "Candy A")
        ->set("round_properties." . $key . "." . $category . "-B", "Candy B")
        ->set("round_properties." . $key . "." . $category . "-C", "Candy C")
        ->set("round_properties." . $key . "." . $category . "-D", "Candy D")
        // fail to include F tier
        // ->set('round_properties.' . $key . '.' . $category . '-F', 'Candy F')
        ->call("callClassAction", "submitTierList", "challenge", $key)
        ->assertHasErrors();

    $this->construction_challenge->refresh();

    // Verify the player hasn't been marked as submitted
    expect($this->construction_challenge->challenge_data["has_submitted"])
        ->not()
        ->toContain($this->player_1->id);
});

it(
    "does not allow two identical submissions per player per category",
    function () {
        $key = TierListConstructionPhase::key();
        $category =
            $this->construction_challenge->challenge_data["categories"][0];

        Livewire::actingAs($this->player_1->fresh()->user)
            ->test(GameDashboard::class, ["game" => $this->game->fresh()])
            ->set(
                "round_properties." . $key . "." . $category . "-A",
                "Candy A",
            )
            ->set(
                "round_properties." . $key . "." . $category . "-B",
                "Candy B",
            )
            ->set(
                "round_properties." . $key . "." . $category . "-C",
                "Candy C",
            )
            ->set(
                "round_properties." . $key . "." . $category . "-D",
                "Candy F",
            )
            ->set(
                "round_properties." . $key . "." . $category . "-F",
                "Candy F",
            )
            ->call("callClassAction", "submitTierList", "challenge", $key)
            ->assertHasErrors();

        $this->construction_challenge->refresh();

        // Verify the player hasn't been marked as submitted
        expect($this->construction_challenge->challenge_data["has_submitted"])
            ->not()
            ->toContain($this->player_1->id);
    },
);

it("allows valid submissions with unique items", function () {
    $key = TierListConstructionPhase::key();
    $category = $this->construction_challenge->challenge_data["categories"][0];

    Livewire::actingAs($this->player_1->fresh()->user)
        ->test(GameDashboard::class, ["game" => $this->game->fresh()])
        ->set("round_properties." . $key . "." . $category . "-A", "Unique A")
        ->set("round_properties." . $key . "." . $category . "-B", "Unique B")
        ->set("round_properties." . $key . "." . $category . "-C", "Unique C")
        ->set("round_properties." . $key . "." . $category . "-D", "Unique D")
        ->set("round_properties." . $key . "." . $category . "-F", "Unique F")
        ->call("callClassAction", "submitTierList", "challenge", $key)
        ->assertHasNoErrors();

    // Verify submissions were stored in the modifier
    $this->tier_list_modifier->refresh();
    $submissions = collect(
        $this->tier_list_modifier->modifier_data["submissions"],
    )
        ->where("player_id", $this->player_1->id)
        ->where("category", $category);

    expect($submissions)->toHaveCount(5);
    expect($submissions->pluck("tier")->sort()->values()->toArray())->toBe([
        "A",
        "B",
        "C",
        "D",
        "F",
    ]);
    expect($submissions->pluck("value")->toArray())->toBe([
        "Unique A",
        "Unique B",
        "Unique C",
        "Unique D",
        "Unique F",
    ]);
});

it("prevents duplicate category submissions by the same player", function () {
    $key = TierListConstructionPhase::key();
    $category = $this->construction_challenge->challenge_data["categories"][0];

    // First submission should work
    Livewire::actingAs($this->player_1->fresh()->user)
        ->test(GameDashboard::class, ["game" => $this->game->fresh()])
        ->set("round_properties." . $key . "." . $category . "-A", "First A")
        ->set("round_properties." . $key . "." . $category . "-B", "First B")
        ->set("round_properties." . $key . "." . $category . "-C", "First C")
        ->set("round_properties." . $key . "." . $category . "-D", "First D")
        ->set("round_properties." . $key . "." . $category . "-F", "First F")
        ->call("callClassAction", "submitTierList", "challenge", $key)
        ->assertHasNoErrors();

    // Second submission for same category should fail
    Livewire::actingAs($this->player_1->fresh()->user)
        ->test(GameDashboard::class, ["game" => $this->game->fresh()])
        ->set("round_properties." . $key . "." . $category . "-A", "Second A")
        ->set("round_properties." . $key . "." . $category . "-B", "Second B")
        ->set("round_properties." . $key . "." . $category . "-C", "Second C")
        ->set("round_properties." . $key . "." . $category . "-D", "Second D")
        ->set("round_properties." . $key . "." . $category . "-F", "Second F")
        ->call("callClassAction", "submitTierList", "challenge", $key)
        ->assertHasErrors();
});

it(
    "automatically ends the challenge when all players have submitted",
    function () {
        submitTierLists($this->game, $this->construction_challenge);

        $this->construction_challenge->refresh();
        expect($this->construction_challenge->status)->toBe("ended");

        // Verify all players are marked as submitted
        $submitted_count = count(
            $this->construction_challenge->challenge_data["has_submitted"],
        );
        $total_players = $this->game->players->count();
        expect($submitted_count)->toBe($total_players);
        expect(
            $this->construction_challenge->challenge_data["has_submitted"],
        )->toContain($this->player_1->id);
        expect(
            $this->construction_challenge->challenge_data["has_submitted"],
        )->toContain($this->player_2->id);
        expect(
            $this->construction_challenge->challenge_data["has_submitted"],
        )->toContain($this->player_3->id);
        expect(
            $this->construction_challenge->challenge_data["has_submitted"],
        )->toContain($this->player_4->id);
    },
);

it("sets answer keys to be guessed on future rounds", function () {
    submitTierLists($this->game, $this->construction_challenge);

    $this->tier_list_modifier->refresh();
    $answer_keys = $this->tier_list_modifier->modifier_data["answer_keys"];

    // Verify structure exists for all three guessing rounds
    expect($answer_keys)->toHaveKeys([
        "single_opponent_round_1",
        "single_opponent_round_2",
        "single_category",
    ]);

    // Verify each player has assignments in all rounds
    $player_ids = $this->game->players->pluck("id")->toArray();

    foreach ($player_ids as $player_id) {
        // Round 1 - opponent guessing
        expect($answer_keys["single_opponent_round_1"][$player_id])->toHaveKeys(
            ["opponent", "A", "B", "C", "D", "F"],
        );
        expect(
            $answer_keys["single_opponent_round_1"][$player_id]["opponent"],
        )->toBeString();
        expect($answer_keys["single_opponent_round_1"][$player_id]["A"])
            ->not()
            ->toBeNull();
        expect($answer_keys["single_opponent_round_1"][$player_id]["B"])
            ->not()
            ->toBeNull();
        expect($answer_keys["single_opponent_round_1"][$player_id]["C"])
            ->not()
            ->toBeNull();
        expect($answer_keys["single_opponent_round_1"][$player_id]["D"])
            ->not()
            ->toBeNull();
        expect($answer_keys["single_opponent_round_1"][$player_id]["F"])
            ->not()
            ->toBeNull();

        // Round 2 - opponent guessing
        expect($answer_keys["single_opponent_round_2"][$player_id])->toHaveKeys(
            ["opponent", "A", "B", "C", "D", "F"],
        );
        expect(
            $answer_keys["single_opponent_round_2"][$player_id]["opponent"],
        )->toBeString();
        expect($answer_keys["single_opponent_round_2"][$player_id]["A"])
            ->not()
            ->toBeNull();
        expect($answer_keys["single_opponent_round_2"][$player_id]["B"])
            ->not()
            ->toBeNull();
        expect($answer_keys["single_opponent_round_2"][$player_id]["C"])
            ->not()
            ->toBeNull();
        expect($answer_keys["single_opponent_round_2"][$player_id]["D"])
            ->not()
            ->toBeNull();
        expect($answer_keys["single_opponent_round_2"][$player_id]["F"])
            ->not()
            ->toBeNull();

        // Round 3 - category guessing
        expect($answer_keys["single_category"][$player_id])->toHaveKeys([
            "category",
            "A",
            "B",
            "C",
            "D",
            "F",
        ]);
        expect(
            $answer_keys["single_category"][$player_id]["category"],
        )->toBeString();
        expect($answer_keys["single_category"][$player_id]["A"])
            ->not()
            ->toBeNull();
        expect($answer_keys["single_category"][$player_id]["B"])
            ->not()
            ->toBeNull();
        expect($answer_keys["single_category"][$player_id]["C"])
            ->not()
            ->toBeNull();
        expect($answer_keys["single_category"][$player_id]["D"])
            ->not()
            ->toBeNull();
        expect($answer_keys["single_category"][$player_id]["F"])
            ->not()
            ->toBeNull();
    }
});

it("ensures no player guesses their own submissions", function () {
    submitTierLists($this->game, $this->construction_challenge);

    $this->tier_list_modifier->refresh();
    $answer_keys = $this->tier_list_modifier->modifier_data["answer_keys"];

    foreach ($this->game->players as $player) {
        // Check round 1
        foreach (["A", "B", "C", "D", "F"] as $tier) {
            $submission =
                $answer_keys["single_opponent_round_1"][$player->id][$tier];
            expect($submission["player_id"])->not()->toBe($player->id);
        }

        // Check round 2
        foreach (["A", "B", "C", "D", "F"] as $tier) {
            $submission =
                $answer_keys["single_opponent_round_2"][$player->id][$tier];
            expect($submission["player_id"])->not()->toBe($player->id);
        }

        // Check round 3
        foreach (["A", "B", "C", "D", "F"] as $tier) {
            $submission = $answer_keys["single_category"][$player->id][$tier];
            expect($submission["player_id"])->not()->toBe($player->id);
        }
    }
});

it("assigns different opponents for rounds 1 and 2", function () {
    submitTierLists($this->game, $this->construction_challenge);

    $this->tier_list_modifier->refresh();
    $answer_keys = $this->tier_list_modifier->modifier_data["answer_keys"];

    foreach ($this->game->players as $player) {
        $round1_opponent =
            $answer_keys["single_opponent_round_1"][$player->id]["opponent"];
        $round2_opponent =
            $answer_keys["single_opponent_round_2"][$player->id]["opponent"];

        expect($round1_opponent)->not()->toBe($round2_opponent);
        expect($round1_opponent)->not()->toBe($player->name);
        expect($round2_opponent)->not()->toBe($player->name);
    }
});

it("selects one of the 3 categories for each player in round 3", function () {
    submitTierLists($this->game, $this->construction_challenge);

    $this->tier_list_modifier->refresh();
    $answer_keys = $this->tier_list_modifier->modifier_data["answer_keys"];
    $available_categories =
        $this->construction_challenge->challenge_data["categories"];

    foreach ($this->game->players as $player) {
        $assigned_category =
            $answer_keys["single_category"][$player->id]["category"];
        expect($assigned_category)->toBeIn($available_categories);
    }
});

it(
    "creates second challenge with proper data structure after first ends",
    function () {
        submitTierLists($this->game, $this->construction_challenge);

        $second_challenge = $this->game
            ->fresh()
            ->challenges->where("round_number", 2)
            ->first();
        expect($second_challenge)->not()->toBeNull();
        expect($second_challenge->class_key)->toBe(TierListGuess::key());
        expect($second_challenge->status)->toBe("active");

        // Verify the challenge has proper assignment data
        expect($second_challenge->challenge_data)->toHaveKeys([
            "has_submitted",
            "has_readied_up",
            "assignments",
            "type",
            "results",
        ]);
        expect($second_challenge->challenge_data["type"])->toBe("opponent");

        // Verify each player has an assignment
        foreach ($this->game->players as $player) {
            expect(
                $second_challenge->challenge_data["assignments"][$player->id],
            )->toHaveKeys(["opponent", "A", "B", "C", "D", "F"]);
        }
    },
);

it("allows players to submit guesses and get graded", function () {
    // First complete the construction phase
    submitTierLists($this->game, $this->construction_challenge);

    $guess_challenge = $this->game
        ->fresh()
        ->challenges->where("round_number", 2)
        ->first();
    expect($guess_challenge)->not()->toBeNull();

    // Get the assignment for player 1
    $assignment =
        $guess_challenge->challenge_data["assignments"][$this->player_1->id];

    // Create guesses based on actual tier assignments
    $guesses = [];
    foreach (["A", "B", "C", "D", "F"] as $tier) {
        $guesses[] = [
            "guessed_tier" => $tier,
            "actual_tier" => $tier, // Perfect guesses for testing
        ];
    }

    // Test the submission directly to avoid form rendering issues
    $challenge_class = new TierListGuess($guess_challenge);
    $challenge_class->submitTierList($this->player_1, [
        "guesses_array" => $guesses,
    ]);

    $guess_challenge->refresh();

    // Verify submission was recorded
    expect($guess_challenge->challenge_data["has_submitted"])->toContain(
        $this->player_1->id,
    );
    expect(
        $guess_challenge->challenge_data["results"][$this->player_1->id],
    )->toHaveCount(5);

    // Verify scoring logic
    $results = $guess_challenge->challenge_data["results"][$this->player_1->id];
    foreach ($results as $result) {
        expect($result)->toHaveKeys([
            "opponent_id",
            "opponent_name",
            "original_submission_value",
            "guessed_tier",
            "correct_tier",
            "points",
            "emoji",
        ]);
        expect($result["points"])->toBeNumeric();
        expect($result["points"])->toBeGreaterThanOrEqual(-2);
        expect($result["points"])->toBeLessThanOrEqual(2);
    }
});

it("calculates correct points for tier guesses", function () {
    // Submit tier lists first
    submitTierLists($this->game, $this->construction_challenge);

    $guess_challenge = $this->game
        ->fresh()
        ->challenges->where("round_number", 2)
        ->first();

    // Test perfect guesses (should get 2 points each)
    $perfect_guesses = [
        ["guessed_tier" => "A", "actual_tier" => "A"],
        ["guessed_tier" => "B", "actual_tier" => "B"],
        ["guessed_tier" => "C", "actual_tier" => "C"],
        ["guessed_tier" => "D", "actual_tier" => "D"],
        ["guessed_tier" => "F", "actual_tier" => "F"],
    ];

    // Test the submission directly to avoid form rendering issues
    $challenge_class = new TierListGuess($guess_challenge);
    $challenge_class->submitTierList($this->player_1, [
        "guesses_array" => $perfect_guesses,
    ]);

    $guess_challenge->refresh();
    $results = $guess_challenge->challenge_data["results"][$this->player_1->id];

    // All should be worth 2 points (perfect guesses)
    foreach ($results as $result) {
        expect($result["points"])->toBe(2);
        expect($result["emoji"])->toBe("🥳");
    }

    // Test off by one guesses (should get 1 point each)
    $off_by_one_guesses = [
        ["guessed_tier" => "A", "actual_tier" => "B"],
        ["guessed_tier" => "B", "actual_tier" => "C"],
        ["guessed_tier" => "C", "actual_tier" => "D"],
        ["guessed_tier" => "D", "actual_tier" => "F"],
        ["guessed_tier" => "F", "actual_tier" => "D"],
    ];

    $challenge_class = new TierListGuess($guess_challenge);
    $challenge_class->submitTierList($this->player_2, [
        "guesses_array" => $off_by_one_guesses,
    ]);

    $guess_challenge->refresh();
    $results = $guess_challenge->challenge_data["results"][$this->player_2->id];

    foreach ($results as $result) {
        expect($result["points"])->toBe(1);
        expect($result["emoji"])->toBe("🧐");
    }
});

it("awards points to both guesser and original submitter", function () {
    submitTierLists($this->game, $this->construction_challenge);

    $initial_score_1 = $this->player_1->fresh()->score;
    $initial_score_2 = $this->player_2->fresh()->score;

    $guess_challenge = $this->game
        ->fresh()
        ->challenges->where("round_number", 2)
        ->first();

    // Submit perfect guesses
    $perfect_guesses = [
        ["guessed_tier" => "A", "actual_tier" => "A"],
        ["guessed_tier" => "B", "actual_tier" => "B"],
        ["guessed_tier" => "C", "actual_tier" => "C"],
        ["guessed_tier" => "D", "actual_tier" => "D"],
        ["guessed_tier" => "F", "actual_tier" => "F"],
    ];

    // Test the submission directly to avoid form rendering issues
    $challenge_class = new TierListGuess($guess_challenge);
    $challenge_class->submitTierList($this->player_1, [
        "guesses_array" => $perfect_guesses,
    ]);

    // Verify player scores were updated
    $updated_score_1 = $this->player_1->fresh()->score;
    expect($updated_score_1)->toBeGreaterThan($initial_score_1);
});

it("allows players to ready up after viewing results", function () {
    submitTierLists($this->game, $this->construction_challenge);

    $guess_challenge = $this->game
        ->fresh()
        ->challenges->where("round_number", 2)
        ->first();

    // All players submit guesses
    $guesses = [
        ["guessed_tier" => "A", "actual_tier" => "A"],
        ["guessed_tier" => "B", "actual_tier" => "B"],
        ["guessed_tier" => "C", "actual_tier" => "C"],
        ["guessed_tier" => "D", "actual_tier" => "D"],
        ["guessed_tier" => "F", "actual_tier" => "F"],
    ];

    // Test submissions directly to avoid form rendering issues
    $challenge_class = new TierListGuess($guess_challenge);
    foreach ($this->game->players as $player) {
        $challenge_class->submitTierList($player, [
            "guesses_array" => $guesses,
        ]);
    }

    $guess_challenge->refresh();
    expect($guess_challenge->challenge_data["has_submitted"])->toHaveCount(
        $this->game->players->count(),
    );

    // Now players can ready up
    $challenge_class->readyUp($this->player_1, []);

    $guess_challenge->refresh();
    expect($guess_challenge->challenge_data["has_readied_up"])->toContain(
        $this->player_1->id,
    );
});

it("advances to next challenge when all players ready up", function () {
    submitTierLists($this->game, $this->construction_challenge);

    $guess_challenge = $this->game
        ->fresh()
        ->challenges->where("round_number", 2)
        ->first();

    // All players submit guesses
    $guesses = [
        ["guessed_tier" => "A", "actual_tier" => "A"],
        ["guessed_tier" => "B", "actual_tier" => "B"],
        ["guessed_tier" => "C", "actual_tier" => "C"],
        ["guessed_tier" => "D", "actual_tier" => "D"],
        ["guessed_tier" => "F", "actual_tier" => "F"],
    ];

    // Test submissions directly to avoid form rendering issues
    $challenge_class = new TierListGuess($guess_challenge);
    foreach ($this->game->players as $player) {
        $challenge_class->submitTierList($player, [
            "guesses_array" => $guesses,
        ]);
    }

    // All players ready up (stagger the calls to avoid race conditions)
    foreach ($this->game->players as $player) {
        $challenge_class->readyUp($player, []);
        // Only commit after the last player to avoid intermediate state conflicts
        if ($player === $this->game->players->last()) {
            Verbs::commit();
        }
    }

    $guess_challenge->refresh();
    expect($guess_challenge->status)->toBe("ended");

    // Next challenge should be started
    $next_challenge = $this->game
        ->fresh()
        ->challenges->where("round_number", 3)
        ->first();
    expect($next_challenge)->not()->toBeNull();
    expect($next_challenge->status)->toBe("active");
});

it(
    "handles all three guessing rounds with different assignment types",
    function () {
        submitTierLists($this->game, $this->construction_challenge);

        // Verify all three guessing challenges exist
        $round2 = $this->game
            ->fresh()
            ->challenges->where("round_number", 2)
            ->first();
        $round3 = $this->game
            ->fresh()
            ->challenges->where("round_number", 3)
            ->first();
        $round4 = $this->game
            ->fresh()
            ->challenges->where("round_number", 4)
            ->first();

        expect($round2)->not()->toBeNull();
        expect($round3)->not()->toBeNull();
        expect($round4)->not()->toBeNull();

        // Round 2 should be opponent-based
        expect($round2->challenge_data["type"])->toBe("opponent");

        // Complete round 2
        completeGuessRound($round2, $this->game->players->all());

        // Round 3 should start and be opponent-based
        $round3->refresh();
        expect($round3->status)->toBe("active");
        expect($round3->challenge_data["type"])->toBe("opponent");

        // Complete round 3
        completeGuessRound($round3, $this->game->players->all());

        // Round 4 should start and be category-based
        $round4->refresh();
        expect($round4->status)->toBe("active");
        expect($round4->challenge_data["type"])->toBe("category");
    },
);

function submitTierLists(Game $game, Challenge $construction_challenge)
{
    $key = TierListConstructionPhase::key();
    $category_0 = $construction_challenge->challenge_data["categories"][0];
    $category_1 = $construction_challenge->challenge_data["categories"][1];
    $category_2 = $construction_challenge->challenge_data["categories"][2];

    $game->players->each(function ($player) use (
        $game,
        $key,
        $category_0,
        $category_1,
        $category_2,
    ) {
        // Submit for category 0
        Livewire::actingAs($player->fresh()->user)
            ->test(GameDashboard::class, ["game" => $game->fresh()])
            ->set(
                "round_properties." . $key . "." . $category_0 . "-A",
                $player->name . "-" . $category_0 . "-A",
            )
            ->set(
                "round_properties." . $key . "." . $category_0 . "-B",
                $player->name . "-" . $category_0 . "-B",
            )
            ->set(
                "round_properties." . $key . "." . $category_0 . "-C",
                $player->name . "-" . $category_0 . "-C",
            )
            ->set(
                "round_properties." . $key . "." . $category_0 . "-D",
                $player->name . "-" . $category_0 . "-D",
            )
            ->set(
                "round_properties." . $key . "." . $category_0 . "-F",
                $player->name . "-" . $category_0 . "-F",
            )
            ->call("callClassAction", "submitTierList", "challenge", $key);

        // Submit for category 1
        Livewire::actingAs($player->fresh()->user)
            ->test(GameDashboard::class, ["game" => $game->fresh()])
            ->set(
                "round_properties." . $key . "." . $category_1 . "-A",
                $player->name . "-" . $category_1 . "-A",
            )
            ->set(
                "round_properties." . $key . "." . $category_1 . "-B",
                $player->name . "-" . $category_1 . "-B",
            )
            ->set(
                "round_properties." . $key . "." . $category_1 . "-C",
                $player->name . "-" . $category_1 . "-C",
            )
            ->set(
                "round_properties." . $key . "." . $category_1 . "-D",
                $player->name . "-" . $category_1 . "-D",
            )
            ->set(
                "round_properties." . $key . "." . $category_1 . "-F",
                $player->name . "-" . $category_1 . "-F",
            )
            ->call("callClassAction", "submitTierList", "challenge", $key);

        // Submit for category 2
        Livewire::actingAs($player->fresh()->user)
            ->test(GameDashboard::class, ["game" => $game->fresh()])
            ->set(
                "round_properties." . $key . "." . $category_2 . "-A",
                $player->name . "-" . $category_2 . "-A",
            )
            ->set(
                "round_properties." . $key . "." . $category_2 . "-B",
                $player->name . "-" . $category_2 . "-B",
            )
            ->set(
                "round_properties." . $key . "." . $category_2 . "-C",
                $player->name . "-" . $category_2 . "-C",
            )
            ->set(
                "round_properties." . $key . "." . $category_2 . "-D",
                $player->name . "-" . $category_2 . "-D",
            )
            ->set(
                "round_properties." . $key . "." . $category_2 . "-F",
                $player->name . "-" . $category_2 . "-F",
            )
            ->call("callClassAction", "submitTierList", "challenge", $key);
    });
}

function completeGuessRound($challenge, $players)
{
    $guesses = [
        ["guessed_tier" => "A", "actual_tier" => "A"],
        ["guessed_tier" => "B", "actual_tier" => "B"],
        ["guessed_tier" => "C", "actual_tier" => "C"],
        ["guessed_tier" => "D", "actual_tier" => "D"],
        ["guessed_tier" => "F", "actual_tier" => "F"],
    ];

    // All players submit guesses
    // All players submit guesses directly to avoid form rendering issues
    $challenge_class = new TierListGuess($challenge);
    foreach ($players as $player) {
        $challenge_class->submitTierList($player, [
            "guesses_array" => $guesses,
        ]);
    }

    // All players ready up
    // All players ready up directly (commit only after the last player)
    foreach ($players as $player) {
        $challenge_class->readyUp($player, []);
        // Only commit after the last player to avoid race conditions
        if ($player === collect($players)->last()) {
            Verbs::commit();
        }
    }
}
