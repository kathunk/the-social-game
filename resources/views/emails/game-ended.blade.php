@component('mail::message')

# 🏆 {{ $game->name }} has ended!

The game has concluded! Check out the final results and see how you did.

@component('mail::button', ['url' => route('game-dashboard', ['game' => $game])])
View Final Results
@endcomponent

@endcomponent
