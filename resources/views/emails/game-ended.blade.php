@component('mail::message')

# 🏆 {{ $game->name }} has ended!

The game has concluded! Check out the final results and see how you did.

<x-button href="{{ route('game-dashboard', ['game' => $game]) }}">View Final Results</x-button>

@endcomponent
