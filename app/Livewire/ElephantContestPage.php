<?php

namespace App\Livewire;

use App\Challenges\ElephantInTheRoom\Support\ImpossibleBotReward;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Public landing page for the impossible-bot bounty at /elephant.
 * Guests see the pitch with a signup/login CTA; logged-in players get a
 * straight link into the app. Livewire (rather than a plain view) so
 * Alpine boots and the elephant card's tile animation runs.
 */
#[Layout('components.layouts.auth')]
class ElephantContestPage extends Component
{
    public function render()
    {
        return view('livewire.elephant-contest-page', [
            'offer' => ImpossibleBotReward::OFFER,
            'offer_url' => ImpossibleBotReward::OFFER_URL,
            'promo_active' => ImpossibleBotReward::isPromoActiveFor(auth()->user()),
            'record' => ImpossibleBotReward::botRecord(),
        ])->title('Beat the Impossible Bot — Elephant in the Room');
    }
}
