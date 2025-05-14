<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Artisan;
use Livewire\Attributes\Computed;
use Livewire\Component;

class NextChallengeButton extends Component
{
    #[Computed]
    public function show()
    {
        $game = auth()?->user()?->currentGame;

        if (! $game || $game->status === 'ended') {
            return false;
        }

        return config('app.env') === 'local';
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
