<?php

namespace App\Challenges\ElephantInTheRoom\Support;

/**
 * Pure board rules for Elephant in the Room: slide paths, cascading,
 * elephant blocking, adjacency, and victory detection on the 4x4 grid.
 *
 * All methods are static and side-effect free — they take a board
 * (array of space => actor id or null, keyed 1-16) plus whatever else
 * they need, and return values. Both the Verbs events and the tests
 * lean on this class; the challenge blade carries a mirrored JS port
 * for optimistic animation and the bot brain.
 *
 * Victory tables are ported verbatim from the original game's BoardLogic
 * (see reference/BoardLogic.php.txt).
 */
class BoardLogic
{
    public const SHAPES = ['square', 'line', 'el', 'zig', 'pyramid'];

    // The bot's check heuristics are strongest for these shapes, so bot
    // games only deal from this subset (same rule as the original game)
    public const BOT_SHAPES = ['square', 'line', 'el'];

    public const ADJACENT_SPACES = [
        1 => [2, 5],
        2 => [1, 3, 6],
        3 => [2, 4, 7],
        4 => [3, 8],
        5 => [1, 6, 9],
        6 => [2, 5, 7, 10],
        7 => [3, 6, 8, 11],
        8 => [4, 7, 12],
        9 => [5, 10, 13],
        10 => [6, 9, 11, 14],
        11 => [7, 10, 12, 15],
        12 => [8, 11, 16],
        13 => [9, 14],
        14 => [10, 13, 15],
        15 => [11, 14, 16],
        16 => [12, 15],
    ];

    public const SLIDING_POSITIONS = [
        1 => [
            'down' => [1, 5, 9, 13],
            'right' => [1, 2, 3, 4],
        ],
        2 => [
            'down' => [2, 6, 10, 14],
        ],
        3 => [
            'down' => [3, 7, 11, 15],
        ],
        4 => [
            'down' => [4, 8, 12, 16],
            'left' => [4, 3, 2, 1],
        ],
        5 => [
            'right' => [5, 6, 7, 8],
        ],
        8 => [
            'left' => [8, 7, 6, 5],
        ],
        9 => [
            'right' => [9, 10, 11, 12],
        ],
        12 => [
            'left' => [12, 11, 10, 9],
        ],
        13 => [
            'right' => [13, 14, 15, 16],
            'up' => [13, 9, 5, 1],
        ],
        14 => [
            'up' => [14, 10, 6, 2],
        ],
        15 => [
            'up' => [15, 11, 7, 3],
        ],
        16 => [
            'up' => [16, 12, 8, 4],
            'left' => [16, 15, 14, 13],
        ],
    ];

    public static function emptyBoard(): array
    {
        return array_fill_keys(range(1, 16), null);
    }

    public static function isSlideConfig(int $space, string $direction): bool
    {
        return isset(self::SLIDING_POSITIONS[$space][$direction]);
    }

    public static function slidingPositions(int $space, string $direction): array
    {
        return self::SLIDING_POSITIONS[$space][$direction];
    }

    public static function adjacentSpaces(int $space): array
    {
        return self::ADJACENT_SPACES[$space];
    }

    /**
     * The elephant blocks a slide when it sits anywhere tiles would need
     * to move: the entry space itself, or the next space along the path
     * behind an unbroken run of occupied spaces.
     */
    public static function slideIsBlockedByElephant(array $board, int $elephant_space, int $space, string $direction): bool
    {
        [$p1, $p2, $p3, $p4] = self::slidingPositions($space, $direction);

        if ($elephant_space === $p1) {
            return true;
        }

        if ($board[$p1] && $elephant_space === $p2) {
            return true;
        }

        if ($board[$p1] && $board[$p2] && $elephant_space === $p3) {
            return true;
        }

        if ($board[$p1] && $board[$p2] && $board[$p3] && $elephant_space === $p4) {
            return true;
        }

        return false;
    }

    public static function validSlides(array $board, int $elephant_space): array
    {
        $slides = [];

        foreach (self::SLIDING_POSITIONS as $space => $directions) {
            foreach (array_keys($directions) as $direction) {
                if (! self::slideIsBlockedByElephant($board, $elephant_space, $space, $direction)) {
                    $slides[] = ['space' => $space, 'direction' => $direction];
                }
            }
        }

        return $slides;
    }

    /**
     * Slide a tile onto the board. Occupants cascade one space along the
     * path; a tile pushed off the far end returns to its owner's hand.
     *
     * @return array{board: array, pushed_off_owner: ?string}
     */
    public static function applySlide(array $board, int $space, string $direction, string $actor_id): array
    {
        [$p1, $p2, $p3, $p4] = self::slidingPositions($space, $direction);

        $new = $board;
        $pushed_off_owner = null;

        if ($board[$p1] && $board[$p2] && $board[$p3]) {
            if ($board[$p4]) {
                $pushed_off_owner = $board[$p4];
            }
            $new[$p4] = $board[$p3];
        }

        if ($board[$p1] && $board[$p2]) {
            $new[$p3] = $board[$p2];
        }

        if ($board[$p1]) {
            $new[$p2] = $board[$p1];
        }

        $new[$p1] = $actor_id;

        return ['board' => $new, 'pushed_off_owner' => $pushed_off_owner];
    }

    public static function validElephantMoves(int $elephant_space): array
    {
        return [...self::adjacentSpaces($elephant_space), $elephant_space];
    }

    public static function victorySets(string $shape): array
    {
        return match ($shape) {
            'square' => self::SQUARE_VICTORIES,
            'line' => self::LINE_VICTORIES,
            'el' => self::EL_VICTORIES,
            'zig' => self::ZIG_VICTORIES,
            'pyramid' => self::PYRAMID_VICTORIES,
            default => [],
        };
    }

    public static function winningSpaces(array $board, string $actor_id, string $shape): array
    {
        foreach (self::victorySets($shape) as $set) {
            $occupied = 0;
            foreach ($set as $space) {
                if ($board[$space] === $actor_id) {
                    $occupied++;
                }
            }

            if ($occupied === 4) {
                return $set;
            }
        }

        return [];
    }

    public static function isVictorious(array $board, string $actor_id, string $shape): bool
    {
        return count(self::winningSpaces($board, $actor_id, $shape)) > 0;
    }

    public const SQUARE_VICTORIES = [
        [1, 2, 5, 6],
        [2, 3, 6, 7],
        [3, 4, 7, 8],
        [5, 6, 9, 10],
        [6, 7, 10, 11],
        [7, 8, 11, 12],
        [9, 10, 13, 14],
        [10, 11, 14, 15],
        [11, 12, 15, 16],
    ];

    public const LINE_VICTORIES = [
        [1, 5, 9, 13],
        [2, 6, 10, 14],
        [3, 7, 11, 15],
        [4, 8, 12, 16],
        [1, 2, 3, 4],
        [5, 6, 7, 8],
        [9, 10, 11, 12],
        [13, 14, 15, 16],
    ];

    public const EL_VICTORIES = [
        // X X X
        //     X
        [1, 2, 3, 7],
        [2, 3, 4, 8],
        [5, 6, 7, 11],
        [6, 7, 8, 12],
        [9, 10, 11, 15],
        [10, 11, 12, 16],

        // X
        // X X X
        [1, 5, 6, 7],
        [2, 6, 7, 8],
        [5, 9, 10, 11],
        [6, 10, 11, 12],
        [9, 13, 14, 15],
        [10, 14, 15, 16],

        //     X
        // X X X
        [3, 5, 6, 7],
        [4, 6, 7, 8],
        [7, 9, 10, 11],
        [8, 10, 11, 12],
        [11, 13, 14, 15],
        [12, 14, 15, 16],

        // X X X
        // X
        [1, 2, 3, 5],
        [2, 3, 4, 6],
        [5, 6, 7, 9],
        [6, 7, 8, 10],
        [9, 10, 11, 13],
        [10, 11, 12, 14],

        // X X
        // X
        // X
        [1, 2, 5, 9],
        [2, 3, 6, 10],
        [3, 4, 7, 11],
        [5, 6, 9, 13],
        [6, 7, 10, 14],
        [7, 8, 11, 15],

        // X X
        //   X
        //   X
        [1, 2, 6, 10],
        [2, 3, 7, 11],
        [3, 4, 8, 12],
        [5, 6, 10, 14],
        [6, 7, 11, 15],
        [7, 8, 12, 16],

        // X
        // X
        // X X
        [1, 5, 9, 10],
        [2, 6, 10, 11],
        [3, 7, 11, 12],
        [5, 9, 13, 14],
        [6, 10, 14, 15],
        [7, 11, 15, 16],

        //   X
        //   X
        // X X
        [2, 6, 9, 10],
        [3, 7, 10, 11],
        [4, 8, 11, 12],
        [6, 10, 13, 14],
        [7, 11, 14, 15],
        [8, 12, 15, 16],
    ];

    public const PYRAMID_VICTORIES = [
        // X X X
        //   X
        [1, 2, 3, 6],
        [2, 3, 4, 7],
        [5, 6, 7, 10],
        [6, 7, 8, 11],
        [9, 10, 11, 14],
        [10, 11, 12, 15],

        //   X
        // X X X
        [2, 5, 6, 7],
        [3, 6, 7, 8],
        [6, 9, 10, 11],
        [7, 10, 11, 12],
        [10, 13, 14, 15],
        [11, 14, 15, 16],

        // X
        // X X
        // X
        [1, 5, 6, 9],
        [2, 6, 7, 10],
        [3, 7, 8, 11],
        [5, 9, 10, 13],
        [6, 10, 11, 14],
        [7, 11, 12, 15],

        //   X
        // X X
        //   X
        [2, 5, 6, 10],
        [3, 6, 7, 11],
        [4, 7, 8, 12],
        [6, 9, 10, 14],
        [7, 10, 11, 15],
        [8, 11, 12, 16],
    ];

    public const ZIG_VICTORIES = [
        // X X
        //   X X
        [1, 2, 6, 7],
        [2, 3, 7, 8],
        [5, 6, 10, 11],
        [6, 7, 11, 12],
        [9, 10, 14, 15],
        [10, 11, 15, 16],

        //   X X
        // X X
        [2, 3, 5, 6],
        [3, 4, 6, 7],
        [6, 7, 9, 10],
        [7, 8, 10, 11],
        [10, 11, 13, 14],
        [11, 12, 14, 15],

        // X
        // X X
        //   X
        [1, 5, 6, 10],
        [2, 6, 7, 11],
        [3, 7, 8, 12],
        [5, 9, 10, 14],
        [6, 10, 11, 15],
        [7, 11, 12, 16],

        //   X
        // X X
        // X
        [2, 5, 6, 9],
        [3, 6, 7, 10],
        [4, 7, 8, 11],
        [6, 9, 10, 13],
        [7, 10, 11, 14],
        [8, 11, 12, 15],
    ];
}
