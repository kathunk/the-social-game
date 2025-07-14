<?php

namespace App\Support;

// @todo I don't know where this belongs, but it's not App/Support/
class WarGameSpace
{
    public function __construct(
        public int $x,
        public string $y,
    ) {
    }

    public function position(): string
    {
        return "{$this->y}-{$this->x}";
    }
}