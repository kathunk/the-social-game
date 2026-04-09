<?php

namespace App\Challenges\MorningRoutine\Rewards;

class Reward
{
    public function __construct(
        public readonly string $key,
        public readonly string $room,
        public readonly string $name,
        public readonly string $flavor,
        public readonly int $points,
        public readonly int $mess,
        public readonly ?string $effect_description = null,
        public readonly ?string $effect_class = null,
    ) {}

    public function hasEffect(): bool
    {
        return $this->effect_class !== null;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'room' => $this->room,
            'name' => $this->name,
            'flavor' => $this->flavor,
            'points' => $this->points,
            'mess' => $this->mess,
            'effect_description' => $this->effect_description,
            'has_effect' => $this->hasEffect(),
        ];
    }
}
