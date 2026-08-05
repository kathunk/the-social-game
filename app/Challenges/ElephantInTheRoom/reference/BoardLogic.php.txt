<?php

namespace App\States\Traits;

use App\States\PlayerState;

trait BoardLogic
{
    public array $board = [
        1 => null,
        2 => null,
        3 => null,
        4 => null,
        5 => null,
        6 => null,
        7 => null,
        8 => null,
        9 => null,
        10 => null,
        11 => null,
        12 => null,
        13 => null,
        14 => null,
        15 => null,
        16 => null,
    ];

    public function adjacentSpaces(int $space)
    {
        $adjacentSpaces = [
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

        return $adjacentSpaces[$space];
    }

    public function slidingPositions(int $space, string $direction)
    {
        $sliding_positions = [
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

        return $sliding_positions[$space][$direction];
    }

    public function slidingPositionOccupants(int $space, string $direction)
    {
        return collect($this->slidingPositions($space, $direction))
            ->map(fn ($position) => $this->board[$position])
            ->toArray();
    }

    public function validSlides(): array
    {
        $all_possible_slides = [
            ['space' => 1, 'direction' => 'down'],
            ['space' => 2, 'direction' => 'down'],
            ['space' => 3, 'direction' => 'down'],
            ['space' => 4, 'direction' => 'down'],
            ['space' => 1, 'direction' => 'right'],
            ['space' => 5, 'direction' => 'right'],
            ['space' => 9, 'direction' => 'right'],
            ['space' => 13, 'direction' => 'right'],
            ['space' => 4, 'direction' => 'left'],
            ['space' => 8, 'direction' => 'left'],
            ['space' => 12, 'direction' => 'left'],
            ['space' => 16, 'direction' => 'left'],
            ['space' => 13, 'direction' => 'up'],
            ['space' => 14, 'direction' => 'up'],
            ['space' => 15, 'direction' => 'up'],
            ['space' => 16, 'direction' => 'up'],
        ];

        return collect($all_possible_slides)->reject(fn ($slide) => $this->slideIsBlockedByElephant($slide['space'], $slide['direction'])
        )->toArray();
    }

    public function slideIsBlockedByElephant(int $space, string $direction)
    {
        $slidingSpaces = $this->slidingPositions($space, $direction);

        if ($this->elephant_space === $space) {
            return true;
        }

        if (
            $this->board[$space]
            && $this->elephant_space === $slidingSpaces[1]
        ) {
            return true;
        }

        if (
            $this->board[$space]
            && $this->board[$slidingSpaces[1]]
            && $this->elephant_space === $slidingSpaces[2]
        ) {
            return true;
        }

        if (
            $this->board[$space]
            && $this->board[$slidingSpaces[1]]
            && $this->board[$slidingSpaces[2]]
            && $this->elephant_space === $slidingSpaces[3]
        ) {
            return true;
        }

        return false;
    }

    public function validElephantMoves(): array
    {
        $valid_elephant_moves = $this->adjacentSpaces($this->elephant_space);

        $valid_elephant_moves[] = $this->elephant_space;

        return $valid_elephant_moves;
    }

    public function victors(array $board): array
    {
        return $this->players()
            ->filter(fn($player) => $this->isVictorious($player, $board))
            ->map(fn($player) => (string) $player->id)
            ->toArray();
    }

    public function winningSpaces(PlayerState $player, array $board): array
    {
        $possible_victory_sets = match ($player->victory_shape) {
            'square' => $this::SQUARE_VICTORIES,
            'line' => $this::LINE_VICTORIES,
            'zig' => $this::ZIG_VICTORIES,
            'el' => $this::EL_VICTORIES,
            'pyramid' => $this::PYRAMID_VICTORIES,
            default => [],
        };

        $winning_sets = collect($possible_victory_sets)
            ->filter(fn($set) => 
                collect($set)
                    ->reduce(function (int $spaces_in_set_occupied_by_player, int $space) use($board, $player) {
                        return $spaces_in_set_occupied_by_player + ($board[$space] === (string) $player->id ? 1 : 0);
                    }, 0) === 4
            );

        if ($winning_sets->count() === 0) {
            return [];
        }

        return $winning_sets->first();
    }

    public function isVictorious(PlayerState $player, array $board)
    {
        return count($this->winningSpaces($player, $board)) > 0;
    }

    const SQUARE_VICTORIES = [
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

    const LINE_VICTORIES = [
        [1, 5, 9, 13],
        [2, 6, 10, 14],
        [3, 7, 11, 15],
        [4, 8, 12, 16],
        [1, 2, 3, 4],
        [5, 6, 7, 8],
        [9, 10, 11, 12],
        [13, 14, 15, 16],
    ];

    const EL_VICTORIES = [
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

    const PYRAMID_VICTORIES = [
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

    const ZIG_VICTORIES = [
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
