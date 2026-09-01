<?php

namespace App\Challenges\ElephantInTheRoom\Support;

use App\Challenges\ElephantInTheRoom\ElephantMatch;
use Illuminate\Support\Arr;

/**
 * The bot's brain. Runs server-side only: the client never proposes bot
 * moves, it just animates whatever the server chose (see playBotTurn on
 * ElephantMatch). All methods are pure functions of the challenge data,
 * so nothing here trusts anything a client sent.
 *
 * The three difficulties, ported from bot-vs-bot simulation research (see
 * research/FINDINGS.md on the elephant-bot-research branch):
 *
 * - normal: the original greedy bot — best single slide by static score,
 *   random elephant move.
 * - hard: scores every (slide, elephant destination) pair by the position
 *   it hands the opponent — deny immediate winning slides, count standing
 *   threats with a fork bonus, grow uncontested shapes.
 * - impossible: hard plus opponent best-reply search on the top candidates.
 */
class BotLogic
{
    // Value of 0-4 own tiles in an opponent-free victory set
    private const PROGRESS = [0, 1, 4, 12, 0];

    // How many top candidates the impossible bot verifies with a
    // best-reply search
    private const LOOKAHEAD_WIDTH = 8;

    /**
     * Picks the bot's full turn for the current challenge data.
     *
     * @return ?array{entry_space: int, direction: string, elephant_to: int}
     */
    public static function chooseTurn(array $data): ?array
    {
        $board = $data['board'];
        $elephant = (int) $data['elephant_space'];
        $shape = $data['victory_shape'];
        $bot = ElephantMatch::BOT_ID;
        $human = collect($data['actor_order'])->first(fn ($actor) => $actor !== $bot);
        $difficulty = $data['bot_difficulty'] ?? 'normal';

        // A third identical slide in a row forfeits the match — every bot
        // is strictly forbidden from ever making one
        $run = BoardLogic::trailingSlideRuns($data['moves'] ?? [])[$bot] ?? null;
        $banned = ($run['count'] ?? 0) >= BoardLogic::MAX_SLIDE_REPEATS
            ? ['space' => $run['entry_space'], 'direction' => $run['direction']]
            : null;

        if (in_array($difficulty, ['hard', 'impossible'], true)) {
            $history = self::positionHistory($data['moves'] ?? []);

            $move = $difficulty === 'impossible'
                ? self::chooseLookaheadMove($board, $elephant, $bot, $human, $shape, $history, $banned)
                : self::chooseTacticianMove($board, $elephant, $bot, $human, $shape, $history, $banned);

            return $move === null ? null : [
                'entry_space' => $move['slide']['space'],
                'direction' => $move['slide']['direction'],
                'elephant_to' => $move['dest'],
            ];
        }

        $slide = self::chooseGreedySlide($board, $elephant, $bot, $human, $shape, $banned);

        return $slide === null ? null : [
            'entry_space' => $slide['space'],
            'direction' => $slide['direction'],
            'elephant_to' => Arr::random(BoardLogic::validElephantMoves($elephant)),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────
    // Normal: the original greedy bot
    // ─────────────────────────────────────────────────────────────────────

    public static function chooseGreedySlide(array $board, int $elephant, string $bot, string $human, string $shape, ?array $banned = null): ?array
    {
        $best = null;
        $best_score = -INF;

        foreach (Arr::shuffle(BoardLogic::validSlides($board, $elephant)) as $slide) {
            if (self::sameSlide($slide, $banned)) {
                continue;
            }

            $next = BoardLogic::applySlide($board, $slide['space'], $slide['direction'], $bot)['board'];
            $score = self::scoreBoard($next, $bot, $human, $shape);

            if ($score > $best_score) {
                $best_score = $score;
                $best = $slide;
            }
        }

        return $best;
    }

    public static function scoreBoard(array $board, string $bot, string $human, string $shape): float
    {
        $score = (float) (self::adjacencyCount($board, $bot) - self::adjacencyCount($board, $human));

        if (self::hasCheck($board, $bot, $shape)) {
            $score += 100;
        }
        if (self::hasCheck($board, $human, $shape)) {
            $score -= 200;
        }
        if (BoardLogic::isVictorious($board, $human, $shape)) {
            $score -= 1000;
        }
        if (BoardLogic::isVictorious($board, $bot, $shape)) {
            $score += 1e9;
        }
        if (self::countTiles($board, $bot) === 8) {
            $score -= 500;
        }

        return $score;
    }

    /**
     * "Check" = one tile away from completing the shape: any victory set
     * with exactly 3 own tiles and the 4th space empty.
     */
    public static function hasCheck(array $board, string $actor_id, string $shape): bool
    {
        foreach (BoardLogic::victorySets($shape) as $set) {
            $own = 0;
            $empty = 0;

            foreach ($set as $space) {
                if ($board[$space] === $actor_id) {
                    $own++;
                } elseif ($board[$space] === null) {
                    $empty++;
                }
            }

            if ($own === 3 && $empty === 1) {
                return true;
            }
        }

        return false;
    }

    public static function adjacencyCount(array $board, string $actor_id): int
    {
        $count = 0;

        for ($space = 1; $space <= 16; $space++) {
            if ($board[$space] !== $actor_id) {
                continue;
            }
            foreach (BoardLogic::adjacentSpaces($space) as $adjacent) {
                if ($board[$adjacent] === $actor_id) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public static function countTiles(array $board, string $actor_id): int
    {
        return count(array_filter($board, fn ($occupant) => $occupant === $actor_id));
    }

    // ─────────────────────────────────────────────────────────────────────
    // Hard & impossible: joint (slide, elephant destination) search
    // ─────────────────────────────────────────────────────────────────────

    public static function winningSlides(array $board, int $elephant, string $actor_id, string $shape): array
    {
        $wins = [];

        foreach (BoardLogic::validSlides($board, $elephant) as $slide) {
            $next = BoardLogic::applySlide($board, $slide['space'], $slide['direction'], $actor_id)['board'];
            if (BoardLogic::isVictorious($next, $actor_id, $shape)) {
                $wins[] = $slide;
            }
        }

        return $wins;
    }

    /**
     * Static score of a completed turn from $me's perspective — the
     * opponent acts next.
     */
    public static function evaluate(array $board, int $elephant, string $me, string $opp, string $shape): float
    {
        $score = 0.0;

        $score -= 120000 * count(self::winningSlides($board, $elephant, $opp, $shape));

        $my_threats = count(self::winningSlides($board, $elephant, $me, $shape));
        $score += 400 * $my_threats;
        if ($my_threats >= 2) {
            $score += 2000;
        }

        foreach (BoardLogic::victorySets($shape) as $set) {
            $mine = 0;
            $theirs = 0;
            $has_elephant = false;

            foreach ($set as $space) {
                if ($board[$space] === $me) {
                    $mine++;
                } elseif ($board[$space] === $opp) {
                    $theirs++;
                }
                if ($space === $elephant) {
                    $has_elephant = true;
                }
            }

            if (! $has_elephant) {
                if ($theirs === 0) {
                    $score += self::PROGRESS[$mine];
                }
                if ($mine === 0) {
                    $score -= self::PROGRESS[$theirs];
                }
            }
        }

        if (self::countTiles($board, $me) === 8) {
            $score -= 600;
        }

        return $score;
    }

    /**
     * Every (slide, elephant destination) pair. A game-ending slide gets an
     * immediate score and no elephant options — the match is over before
     * the elephant phase.
     *
     * @return array<array{slide: array, dest: int, board: array, immediate: ?float}>
     */
    public static function jointMoves(array $board, int $elephant, string $me, string $opp, string $shape, ?array $banned = null): array
    {
        $moves = [];

        foreach (BoardLogic::validSlides($board, $elephant) as $slide) {
            if (self::sameSlide($slide, $banned)) {
                continue;
            }

            $next = BoardLogic::applySlide($board, $slide['space'], $slide['direction'], $me)['board'];
            $my_win = BoardLogic::isVictorious($next, $me, $shape);
            $their_win = BoardLogic::isVictorious($next, $opp, $shape);

            if ($my_win || $their_win) {
                $moves[] = [
                    'slide' => $slide,
                    'dest' => $elephant,
                    'board' => $next,
                    'immediate' => $my_win && $their_win ? 0.0 : ($my_win ? 1e9 : -1e9),
                ];

                continue;
            }

            foreach (BoardLogic::validElephantMoves($elephant) as $dest) {
                $moves[] = ['slide' => $slide, 'dest' => $dest, 'board' => $next, 'immediate' => null];
            }
        }

        return $moves;
    }

    /**
     * "Hard": 1-ply — hand the opponent the worst static position. The
     * history maps positionKey -> times the bot has produced it, so it
     * varies rather than shuffling the same tiles forever.
     */
    public static function chooseTacticianMove(array $board, int $elephant, string $me, string $opp, string $shape, array $history = [], ?array $banned = null): ?array
    {
        $best = null;
        $best_score = -INF;

        foreach (Arr::shuffle(self::jointMoves($board, $elephant, $me, $opp, $shape, $banned)) as $move) {
            $score = $move['immediate'] ?? self::evaluate($move['board'], $move['dest'], $me, $opp, $shape);

            if ($move['immediate'] === null) {
                $score -= 800 * ($history[self::positionKey($move['board'], $move['dest'])] ?? 0);
            }

            if ($score > $best_score) {
                $best_score = $score;
                $best = $move;
            }
        }

        return $best;
    }

    public static function bestReplyScore(array $board, int $elephant, string $me, string $opp, string $shape): float
    {
        $best = -INF;

        foreach (self::jointMoves($board, $elephant, $me, $opp, $shape) as $move) {
            $score = $move['immediate'] ?? self::evaluate($move['board'], $move['dest'], $me, $opp, $shape);
            if ($score > $best) {
                $best = $score;
            }
            if ($best >= 1e9) {
                break;
            }
        }

        return $best === -INF ? 0.0 : $best;
    }

    /**
     * "Impossible": 2-ply — judge each top candidate by the opponent's
     * best reply to it.
     */
    public static function chooseLookaheadMove(array $board, int $elephant, string $me, string $opp, string $shape, array $history = [], ?array $banned = null): ?array
    {
        $scored = collect(Arr::shuffle(self::jointMoves($board, $elephant, $me, $opp, $shape, $banned)))
            ->map(fn ($move) => [
                'move' => $move,
                'score' => $move['immediate'] ?? self::evaluate($move['board'], $move['dest'], $me, $opp, $shape),
            ])
            ->sortByDesc('score')
            ->values();

        if ($scored->isEmpty()) {
            return null;
        }
        if ($scored[0]['score'] >= 1e9) {
            return $scored[0]['move'];
        }

        $best = null;
        $best_total = -INF;

        foreach ($scored->take(self::LOOKAHEAD_WIDTH) as ['move' => $move, 'score' => $score]) {
            if ($move['immediate'] !== null) {
                $total = $score;
            } elseif (self::countTiles($move['board'], $opp) === 8) {
                // Opponent's hand is empty — they get skipped, so there is
                // no reply to search
                $total = $score;
            } else {
                $total = -self::bestReplyScore($move['board'], $move['dest'], $opp, $me, $shape) + 0.001 * $score;
            }

            if ($move['immediate'] === null) {
                $total -= 800 * ($history[self::positionKey($move['board'], $move['dest'])] ?? 0);
            }

            if ($total > $best_total) {
                $best_total = $total;
                $best = $move;
            }
        }

        return $best;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Position history: derived from the move log, never trusted from a client
    // ─────────────────────────────────────────────────────────────────────

    public static function positionKey(array $board, int $dest): string
    {
        $key = '';
        for ($space = 1; $space <= 16; $space++) {
            $key .= ($board[$space] ?? '.').',';
        }

        return $key.$dest;
    }

    /**
     * Replays the move log from an empty board and counts how many times
     * the bot has produced each (post-slide board, elephant destination)
     * position, so the variety penalty survives page loads and can't be
     * tampered with client-side.
     */
    public static function positionHistory(array $moves): array
    {
        $board = BoardLogic::emptyBoard();
        $history = [];

        foreach ($moves as $move) {
            if (($move['type'] ?? null) === 'tile') {
                $board = BoardLogic::applySlide($board, (int) $move['entry_space'], $move['direction'], (string) $move['actor_id'])['board'];
            } elseif (($move['type'] ?? null) === 'elephant' && (string) $move['actor_id'] === ElephantMatch::BOT_ID) {
                $key = self::positionKey($board, (int) $move['to_space']);
                $history[$key] = ($history[$key] ?? 0) + 1;
            }
        }

        return $history;
    }

    private static function sameSlide(?array $a, ?array $b): bool
    {
        return $a !== null && $b !== null
            && $a['space'] === $b['space']
            && $a['direction'] === $b['direction'];
    }
}
