@component('mail::message')

# {{ $game->name }} is afoot!

Make your first move. Good luck 😈

<x-button href="{{ route('game-dashboard', ['game' => $game]) }}">View Game</x-button>

@endcomponent
