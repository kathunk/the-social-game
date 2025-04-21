<?php

namespace App\GameTemplates;

class TestTemplate extends GameTemplate
{
    const GAME_NAME = 'A game for tests';

    const TEAM_NAMES = [
        'Breeze',
        'Cashier',
        'Debugbar',
        'Intertia',
        'Livewire',
        'Pest',
        'Reverb',
        'Spatie',
        'Tailwind',
        'Verbs',
    ];

    public function starts_at()
    {
        return now();
    }

    public function ends_at()
    {
        return now()->addHours(24);
    }
}
