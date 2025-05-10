<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Artisan;
use PhpParser\Node\Stmt\Return_;

class NextChallengeButton extends Component
{
    #[Computed]
    public function show()
    {
        $game = auth()?->user()?->currentGame;

        if (! $game) {
            return false;
        }

        return ($game->challenges->last()->id !== $game->currentChallenge->id)
                && config('app.env') === 'local';
    }

    public function nextChallenge()
    {
        if (config('app.env') !== 'local') {
            abort(403, 'This action is unauthorized.');
        }

        Artisan::call('dev:next');

        $this->dispatch('challenge-complete');
    }

    public function render()
    {
        return view('livewire.next-challenge-button');
    }
}
