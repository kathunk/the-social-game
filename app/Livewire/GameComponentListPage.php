<?php

namespace App\Livewire;

use App\Challenges\ChallengeRegistry;
use App\Models\GameTemplate;
use App\Modifiers\ModifierRegistry;
use Livewire\Attributes\Computed;
use Livewire\Component;

class GameComponentListPage extends Component
{
    #[Computed]
    public function challenges()
    {
        return collect(ChallengeRegistry::getAll())->sortBy(fn ($c) => $c::NAME);
    }

    #[Computed]
    public function modifiers()
    {
        return collect(ModifierRegistry::getAll())->sortBy(fn ($m) => $m::NAME);
    }

    #[Computed]
    public function templates()
    {
        return GameTemplate::all()->sortBy(fn ($t) => $t->name)
            ->map(function ($t) {
                return [
                    'name' => $t->name,
                    'challenges' => collect($t->challenges)->pluck('challenge_keys')->flatten()->toArray(),
                    'modifiers' => $t->modifiers,
                    'id' => $t->id,
                    'type' => $t->type,
                    'description' => $t->description,
                    'game_mode_id' => $t->game_mode_id,
                ];
            });
    }

    public function render()
    {
        return view('livewire.game-component-list-page');
    }
}
