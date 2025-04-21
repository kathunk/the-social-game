<?php

namespace App\GameTemplates;

use App\Challenges\Classes\PyramidScheme;
use App\Challenges\Classes\StayOnMessage;
use Illuminate\Support\Carbon;

class Laracon2025 extends GameTemplate
{
    const GAME_NAME = 'Laracon 2025';

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
        return Carbon::parse('2025-07-29 5:00:00 MST');
    }

    public function ends_at()
    {
        return Carbon::parse('2025-07-30 17:00:00 MST');
    }

    public function challenges()
    {
        return collect([
            [
                'class' => PyramidScheme::class,
                'starts_at' => $this->starts_at->copy(),
                'ends_at' => $this->starts_at->copy()->addHours(7),
            ],
            [
                'class' => StayOnMessage::class,
                'starts_at' => $this->starts_at->copy()->addHours(7),
                'ends_at' => $this->starts_at->copy()->addHours(8),
            ],
        ]);
    }
}
