@component('mail::message')

![The Social Game]({{ url('/images/OG.png') }})

# {{ $game->name }} has started!

{{ $game->name }} has started!

@component('mail::button', ['url' => $game->url])
View Game
@endcomponent

Jump in, the water is warm!

Thanks,<br>
{{ config('app.name') }}
@endcomponent
