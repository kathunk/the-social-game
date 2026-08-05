<?php

namespace App\States\Traits;

trait BotLogic
{
    public function selectBotTileMove(array $board)
    {
        $possible_moves_ranked = collect($this->validSlides($board))
            ->shuffle()
            ->map(function ($slide) use($board) {
                $score = $this->boardScore($this->hypotheticalBoardAfterSlide($slide['space'], $slide['direction'], $board));

                return [
                    'space' => $slide['space'],
                    'direction' => $slide['direction'],
                    'score' => $score,
                ];
            })
            ->sortByDesc('score');

        return $possible_moves_ranked;
    }

    public function selectBotElephantMove()
    {
        // @todo actually score elephant moves
        return collect($this->validElephantMoves())
            ->shuffle()
            ->map(fn ($move) => [
                'space' => $move,
                'score' => 1,
            ])
            ->sortByDesc('score');   
    }

    public function hypotheticalBoardAfterSlide(int $space, string $direction, array $board)
    {
        $hypothetical_board = $board;

        $second_space = $this->slidingPositions($space, $direction)[1];
        $third_space = $this->slidingPositions($space, $direction)[2];
        $fourth_space = $this->slidingPositions($space, $direction)[3];

        if (
            $hypothetical_board[$space]
            && $hypothetical_board[$second_space]
            && $hypothetical_board[$third_space]
        ) {
            $hypothetical_board[$fourth_space] = $board[$third_space];
        }

        if (
            $hypothetical_board[$space]
            && $hypothetical_board[$second_space]
        ) {
            $hypothetical_board[$third_space] = $board[$second_space];
        }

        if ($hypothetical_board[$space]) {
            $hypothetical_board[$second_space] = $board[$space];
        }

        $hypothetical_board[$space] = (string) $this->player_2_id;

        return $hypothetical_board;
    }

    public function boardScore(array $hypothetical_board)
    {
        $bot_id = $this->current_player_id;

        $player_id = $bot_id === $this->player_1_id 
            ? $this->player_2_id 
            : $this->player_1_id;

        $score = 0;

        $score += $this->numberOfAdjacentTilesFor($bot_id, $hypothetical_board);

        $score -= $this->numberOfAdjacentTilesFor($player_id, $hypothetical_board);

        if($this->hypotheticallyHasCheck($bot_id, $hypothetical_board)) {
            $score += 100;
        }

        if($this->hypotheticallyHasCheck($player_id, $hypothetical_board)) {
            $score -= 200;
        }

        if(collect($this->victors($hypothetical_board))->contains($player_id)) {
            $score -= 1000;
        }

        if(collect($this->victors($hypothetical_board))->contains($bot_id)) {
            $score += 1000000000;
        }

        if($this->botHypotheticallyRunsOutOfTiles($hypothetical_board)) {
            $score -= 500;
        }

        return $score;
    }

    public function spacesOccupiedBy(string $player_id, array $hypothetical_board)
    {
        return collect($hypothetical_board)
            ->filter(fn ($occupant) => $occupant === $player_id)
            ->keys();
    }

    public function numberOfAdjacentTilesFor(string $player_id, array $hypothetical_board)
    {
        return $this->spacesOccupiedBy($player_id, $hypothetical_board)
            ->map(fn ($space) => collect($this->adjacentSpaces($space))
                ->filter(fn ($adjacent_space) => $hypothetical_board[$adjacent_space] === $player_id)
                ->count()
            )
            ->sum();
    }

    public function botHypotheticallyRunsOutOfTiles(array $hypothetical_board)
    {      
        return collect($hypothetical_board)
            ->filter(fn ($occupant) => $occupant === $this->current_player_id)
            ->count() === 8;
    }

    public function hypotheticallyHasCheck(string $player_id, array $hypothetical_board)
    {
        $player_victory_shape = $player_id === $this->player_1_id
            ? $this->player_1_victory_shape
            : $this->player_2_victory_shape;

        return match($player_victory_shape) {
            'el' => $this->hasSquareCheck($player_id, $hypothetical_board) || $this->hasElCheck($player_id, $hypothetical_board),
            'line' => $this->hasLineCheck($player_id, $hypothetical_board),
            'zig' => $this->hasSquareCheck($player_id, $hypothetical_board) || $this->hasZigCheck($player_id, $hypothetical_board),
            'pyramid' => $this->hasPyramidCheck($player_id, $hypothetical_board),
            'square' => $this->hasSquareCheck($player_id, $hypothetical_board),
        };
    }

    public function hasSquareCheck(string $player_id, array $hypothetical_board)
    {
        // @todo modify with "triangle of 3 that I can't block with elephant"
        // also this is not totally exhaustive. But the bot is plenty hard, so whatever.

        $every_triangle_check = [
            [1, 2, 5],
            [1, 2, 6],
            [2, 3, 6],
            [2, 3, 7],
            [3, 4, 7],
            [3, 4, 8],
            [9, 13, 14],
            [10, 13, 14],
            [10, 14, 15],
            [11, 14, 15],
            [11, 15, 16],
            [12, 15, 16],
            [1, 5, 6],
            [5, 6, 9],
            [5, 9, 10],
            [9, 10, 13],
            [4, 7, 8],
            [7, 8, 12],
            [8, 11, 12],
            [11, 12, 16],
        ];

        return collect($every_triangle_check)
            ->map(fn ($triangle) =>
                $hypothetical_board[$triangle[0]] === $player_id
                && $hypothetical_board[$triangle[1]] === $player_id
                && $hypothetical_board[$triangle[2]] === $player_id
            )
            ->contains(true);

        // @todo: add opponent has a zigzag of 4 with the ability to push it into place
        // bonus: modify the above with "zigzag of 4 that I can't block with elephant"

        return false;
    }

    public function hasLineCheck(string $player_id, array $hypothetical_board)
    {
        $every_line_check = [
            [1, 2, 3],
            [1, 2, 4],
            [1, 3, 4],
            [2, 3, 4],

            [5, 6, 3, 8],
            [5, 6, 11, 8],
            [5, 7, 2, 8],
            [5, 7, 12, 8],
            [6, 7, 8],

            [9, 10, 11],
            [9, 10, 7, 12],
            [9, 10, 15, 12],
            [9, 6, 11, 12],
            [9, 14, 11, 12],
            [10, 11, 12],

            [13, 14, 15],
            [13, 14, 16],
            [13, 15, 16],
            [14, 15, 16],

            [1, 5, 9],
            [1, 5, 13],
            [1, 9, 13],
            [5, 9, 13],

            [2, 6, 10],
            [2, 6, 9, 14],
            [2, 6, 11, 14],
            [2, 5, 10, 14],
            [2, 7, 10, 14],
            [6, 10, 14],

            [3, 7, 11],
            [3, 7, 10, 15],
            [3, 7, 12, 15],
            [3, 6, 11, 15],
            [3, 8, 11, 15],
            [7, 11, 15],

            [4, 8, 12],
            [4, 12, 16],
            [4, 8, 16],
            [8, 12, 16],
        ];

        return collect($every_line_check)
            ->map(fn ($line) =>
                $hypothetical_board[$line[0]] === $player_id
                && $hypothetical_board[$line[1]] === $player_id
                && $hypothetical_board[$line[2]] === $player_id
            )
            ->contains(true);

        return false;
    }

    public function hasZigCheck(string $player_id, array $hypothetical_board)
    {
        $other_zig_checks = [
            // x x -
            // - - x
            [1, 2, 7],
            [2, 3, 8],
            [5, 6, 11],
            [6, 7, 12],
            [9, 10, 15],
            [10, 11, 16],

            // - x x
            // x - -
            [2, 3, 5],
            [3, 4, 6],
            [6, 7, 9],
            [7, 8, 10],
            [10, 11, 13],
            [11, 12, 14],

            // x - - 
            // - x x
            [1, 6, 7],
            [2, 7, 8],
            [5, 10, 11],
            [6, 11, 12],
            [9, 14, 15],
            [10, 15, 16],

            // - - x
            // x x -
            [3, 5, 6],
            [4, 6, 7],
            [7, 9, 10],
            [8, 10, 11],
            [11, 13, 14],
            [12, 14, 15],

            // x -
            // x -
            // - x
            [1, 5, 10],
            [2, 6, 11],
            [3, 7, 12],
            [5, 9, 14],
            [6, 10, 15],
            [7, 11, 16],

            // - x
            // - x
            // x -
            [2, 6, 9],
            [3, 7, 10],
            [4, 8, 11],
            [6, 10, 13],
            [7, 11, 14],
            [8, 12, 15],

            // x -
            // - x
            // - x
            [1, 6, 10],
            [2, 7, 11],
            [3, 8, 12],
            [5, 10, 14],
            [6, 11, 15],
            [7, 12, 16],

            // - x
            // x -
            // x -
            [2, 5, 9],
            [3, 6, 10],
            [4, 7, 11],
            [6, 9, 13],
            [7, 10, 14],
            [8, 11, 15],

            // other triangles that are not in the above

            [2, 5, 6],
            [3, 7, 8],
            [9, 10, 14],
            [11, 12, 15],
            [6, 7, 11],
            [7, 10, 11],
            [6, 7, 10],
            [6, 10, 11],
        ];

        return collect($other_zig_checks)
            ->map(fn ($line) =>
                $hypothetical_board[$line[0]] === $player_id
                && $hypothetical_board[$line[1]] === $player_id
                && $hypothetical_board[$line[2]] === $player_id
            )
            ->contains(true);

        return false;
    }

    public function hasElCheck(string $player_id, array $hypothetical_board)
    {
        $other_el_checks = [
            // x x
            // - -
            // x -
            [1, 2, 9],
            [5, 6, 13],

            // x x
            // - -
            // - x
            [3, 4, 12],
            [7, 8, 16],

            // x - x
            // x - -
            [1, 3, 5],
            [2, 4, 6],

            // - - x
            // x - x
            [11, 13, 15],
            [12, 14, 16],

            // x x x
            [1, 2, 3],
            [2, 3, 4],
            [5, 6, 7],
            [6, 7, 8],
            [9, 10, 11],
            [10, 11, 12],
            [13, 14, 15],
            [14, 15, 16],

            // x
            // x
            // x
            [1, 5, 9],
            [5, 9, 13],
            [2, 6, 10],
            [6, 10, 14],
            [3, 7, 11],
            [7, 11, 15],
            [4, 8, 12],
            [8, 12, 16],

            // x x -
            // - - x
            [1, 2, 7],
            [2, 3, 8],
            [5, 6, 11],
            [6, 7, 12],
            [9, 10, 15],
            [10, 11, 16],

            // - x x
            // x - -
            [2, 3, 5],
            [3, 4, 6],
            [6, 7, 9],
            [7, 8, 10],
            [10, 11, 13],
            [11, 12, 14],

            // x - - 
            // - x x
            [1, 6, 7],
            [2, 7, 8],
            [5, 10, 11],
            [6, 11, 12],
            [9, 14, 15],
            [10, 15, 16],

            // - - x
            // x x -
            [3, 5, 6],
            [4, 6, 7],
            [7, 9, 10],
            [8, 10, 11],
            [11, 13, 14],
            [12, 14, 15],

            // x -
            // x -
            // - x
            [1, 5, 10],
            [2, 6, 11],
            [3, 7, 12],
            [5, 9, 14],
            [6, 10, 15],
            [7, 11, 16],

            // - x
            // - x
            // x -
            [2, 6, 9],
            [3, 7, 10],
            [4, 8, 11],
            [6, 10, 13],
            [7, 11, 14],
            [8, 12, 15],

            // x -
            // - x
            // - x
            [1, 6, 10],
            [2, 7, 11],
            [3, 8, 12],
            [5, 10, 14],
            [6, 11, 15],
            [7, 12, 16],

            // - x
            // x -
            // x -
            [2, 5, 9],
            [3, 6, 10],
            [4, 7, 11],
            [6, 9, 13],
            [7, 10, 14],
            [8, 11, 15],
        ];

        return collect($other_el_checks)
            ->map(fn ($line) =>
                $hypothetical_board[$line[0]] === $player_id
                && $hypothetical_board[$line[1]] === $player_id
                && $hypothetical_board[$line[2]] === $player_id
            )
            ->contains(true);

        return false;
    }

    public function hasPyramidCheck(string $player_id, array $hypothetical_board)
    {
        $other_pyramid_checks = [
            // x - x
            // - x -
            [1, 3, 6],
            [2, 4, 7],
            [9, 11, 14],
            [10, 12, 15],

            // - x -
            // x - x
            [2, 5, 7],
            [3, 6, 8],
            [10, 13, 15],
            [11, 14, 16],

            // x -
            // - x
            // x -
            [1, 6, 9],
            [3, 8, 11],
            [5, 10, 13],
            [7, 12, 15],

            // - x
            // x -
            // - x
            [2, 5, 10],
            [4, 7, 12],
            [6, 9, 14],
            [8, 11, 16],

            // x x x
            [1, 2, 3],
            [2, 3, 4],
            [5, 6, 7],
            [6, 7, 8],
            [9, 10, 11],
            [10, 11, 12],
            [13, 14, 15],
            [14, 15, 16],

            // x
            // x
            // x
            [1, 5, 9],
            [5, 9, 13],
            [2, 6, 10],
            [6, 10, 14],
            [3, 7, 11],
            [7, 11, 15],
            [4, 8, 12],
            [8, 12, 16],

            // x x
            // x -
            [2, 3, 6],
            [3, 4, 7],
            [5, 6, 9],
            [6, 7, 10],
            [7, 8, 11],
            [9, 10, 13],
            [10, 11, 14],
            [11, 12, 15],

            // x x
            // - x
            [1, 2, 6],
            [2, 3, 7],
            [5, 6, 10],
            [6, 7, 11],
            [7, 8, 12],
            [9, 10, 14],
            [10, 11, 15],
            [11, 12, 16],

            // x -
            // x x
            [1, 5, 6],
            [2, 6, 7],
            [3, 7, 8],
            [5, 9, 10],
            [6, 10, 11],
            [7, 11, 12],
            [10, 14, 15],
            [11, 15, 16],

            // - x
            // x x
            [2, 5, 6],
            [3, 6, 7],
            [4, 7, 8],
            [6, 9, 10],
            [7, 10, 11],
            [8, 11, 12],
            [10, 13, 14],
            [11, 14, 15],
            [12, 15, 16],
        ];

        return collect($other_pyramid_checks)
            ->map(fn ($line) =>
                $hypothetical_board[$line[0]] === $player_id
                && $hypothetical_board[$line[1]] === $player_id
                && $hypothetical_board[$line[2]] === $player_id
            )
            ->contains(true);

        return false;
    }
}
