@php
    $theme_slug = auth()->user()->currentGame?->css_theme_slug ?? 'default';
@endphp

<x-layouts.app.sidebar
    :title="$title ?? null"
    game_theme="{{ $theme_slug }}"
    class="lg:mt-12"
>
    <flux:main class="min-h-dvh bg-[var(--bg)] text-[var(--fg)]" game-theme="{{ $theme_slug }}">
        <div class="w-full max-w-screen-sm mx-auto theme-surface" >
            {{ $slot }}
        </div>
    </flux:main>
</x-layouts.app.sidebar>
