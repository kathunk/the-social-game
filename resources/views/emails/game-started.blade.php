@component('mail::message')

# {{ $game->name }} is afoot!

Make your first move. Good luck 😈

@component('mail::button', ['url' => route('game-dashboard', ['game' => $game])])
View Game
@endcomponent

@endcomponent
