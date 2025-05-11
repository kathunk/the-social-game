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
            'default' => $user,
            {{-- "{$user->currentPlayer->team->theme()}" => $user?->currentPlayer?->team?->theme(), --}}
            'min-h-screen bg-[var(--color-background)]',
        ])
    >
        <flux:main class="w-full max-w-screen">
            {{ $slot }}
        </flux:main>

        @fluxScripts
    </body>
</html>
