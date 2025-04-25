<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main class="w-full max-w-screen-sm mx-auto">
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
