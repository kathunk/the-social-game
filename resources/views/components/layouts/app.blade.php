<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main class="max-w-screen-sm mx-auto">
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
