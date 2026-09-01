<?php

namespace App\Challenges\ElephantInTheRoom\Support;

use App\Challenges\ElephantInTheRoom\ElephantMatch;
use App\Models\Challenge;
use App\Models\ElephantOfferCode;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * The impossible-bot bounty: beat the bot on impossible mode and earn a
 * one-time Catacombian.com offer code. One code per USER (players are
 * per-game rows, and the one-tap rematch would otherwise print codes).
 *
 * Codes are single-use Stripe promotion codes pre-generated on the
 * Catacombian side and loaded into the pool with the
 * `elephant:add-offer-codes` artisan command. Rewards are claimed at win
 * time in ElephantMatch::onChallengeEnded — old wins never trigger
 * retroactively.
 */
class ImpossibleBotReward
{
    // What the code buys — used verbatim in the promo surfaces and emails
    public const OFFER = '50% off Colossi at Catacombian.com';

    public const OFFER_URL = 'https://catacombian.com/colossi';

    public const OWNER_EMAIL = 'hello@catacombian.com';

    // The bot's public record only counts matches from the server-side-brain
    // era; earlier impossible games ran a (cheatable) client-side brain
    public const RECORD_SINCE = '2026-09-01';

    // The owner notification starts warning when the pool gets this low
    public const LOW_POOL_THRESHOLD = 3;

    /**
     * Whether to promote the bounty to this user: there must be codes left,
     * and they must not have earned theirs already. A null user (guest
     * landing page) just checks the pool.
     */
    public static function isPromoActiveFor(?User $user): bool
    {
        if (self::remainingCodes() === 0) {
            return false;
        }

        return $user === null || ! self::hasClaimed($user);
    }

    public static function remainingCodes(): int
    {
        return ElephantOfferCode::whereNull('claimed_by_user_id')->count();
    }

    public static function hasClaimed(User $user): bool
    {
        return ElephantOfferCode::where('claimed_by_user_id', $user->id)->exists();
    }

    /**
     * Atomically claims the next unused code for a user's winning game.
     * Returns null when the user already has one, or when the pool is empty
     * (the caller emails the owner an SOS in that case).
     */
    public static function claimCodeFor(User $user, int $challenge_id): ?ElephantOfferCode
    {
        if (self::hasClaimed($user)) {
            return null;
        }

        $code = ElephantOfferCode::whereNull('claimed_by_user_id')->orderBy('id')->first();

        if ($code === null) {
            return null;
        }

        // Conditional update so two simultaneous winners can't grab the
        // same code — the loser of the race falls through to the next row
        $claimed = ElephantOfferCode::where('id', $code->id)
            ->whereNull('claimed_by_user_id')
            ->update([
                'claimed_by_user_id' => $user->id,
                'source_challenge_id' => $challenge_id,
                'claimed_at' => now(),
            ]) === 1;

        if (! $claimed) {
            return self::claimCodeFor($user, $challenge_id);
        }

        return $code->fresh();
    }

    /**
     * The claim that came from a specific game, so the post-game screen can
     * celebrate exactly the win that earned the code (a second win by the
     * same player shows a plain result).
     */
    public static function claimForChallenge(int $challenge_id): ?ElephantOfferCode
    {
        return ElephantOfferCode::where('source_challenge_id', $challenge_id)->first();
    }

    /**
     * The bot's public record on impossible mode: [bot wins, human wins].
     * Outright wins only — draws count for neither side.
     */
    public static function botRecord(): array
    {
        $bot_wins = 0;
        $human_wins = 0;

        Challenge::query()
            ->where('class_key', ElephantMatch::key())
            ->where('status', 'ended')
            ->where('updated_at', '>=', Carbon::parse(self::RECORD_SINCE)->startOfDay())
            ->get()
            ->each(function (Challenge $challenge) use (&$bot_wins, &$human_wins) {
                $data = $challenge->challenge_data;

                if (! ($data['is_bot_game'] ?? false) || ($data['bot_difficulty'] ?? null) !== 'impossible') {
                    return;
                }
                if (($data['match_status'] ?? null) !== 'complete') {
                    return;
                }

                $victors = array_map('strval', $data['victor_ids'] ?? []);

                if ($victors === [ElephantMatch::BOT_ID]) {
                    $bot_wins++;
                } elseif (self::soloHumanVictorId($data) !== null) {
                    $human_wins++;
                }
            });

        return ['bot' => $bot_wins, 'humans' => $human_wins];
    }

    /**
     * Whether a just-finished match's data earns the reward: a completed
     * impossible bot game the human won outright.
     */
    public static function qualifiesAsWin(array $data): bool
    {
        return ($data['is_bot_game'] ?? false)
            && ($data['bot_difficulty'] ?? null) === 'impossible'
            && ($data['match_status'] ?? null) === 'complete'
            && self::soloHumanVictorId($data) !== null;
    }

    /**
     * The sole human victor of a match, or null if the bot won, nobody won,
     * or the shapes completed simultaneously (a draw does not beat the bot).
     */
    public static function soloHumanVictorId(array $data): ?string
    {
        $victors = array_map('strval', $data['victor_ids'] ?? []);

        if (count($victors) !== 1 || $victors[0] === ElephantMatch::BOT_ID) {
            return null;
        }

        return $victors[0];
    }
}
