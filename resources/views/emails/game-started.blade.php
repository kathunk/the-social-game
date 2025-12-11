@component('mail::message')

# {{ $game->name }} is afoot!

Make your first move. Good luck 😈

<button href="{{ route('game-dashboard', ['game' => $game]) }}">View Game</button>
@endcomponent
