<!DOCTYPE html>
<html id="hello" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body>
        <flux:main class="w-full max-w-screen">
            {{ $slot }}
        </flux:main>

        @fluxScripts
</body>
</html>
