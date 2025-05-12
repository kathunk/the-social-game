<!DOCTYPE html>
<html id="hello" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    @php
        $user = auth()->user();
    @endphp
    <body
        @class([
            'bg-zinc-50 dark:bg-zinc-800' => ! $user?->currentPlayer?->team,
            "{$user->currentPlayer?->team?->theme()} bg-[var(--color-background)]" => $user->currentPlayer?->team?->theme(),
            'min-h-screen',
        ])
    >
        <flux:main class="w-full max-w-screen">
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>
