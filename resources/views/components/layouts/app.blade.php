<x-layouts.app.sidebar :title="$title ?? null" class="bg-light-orange">
    <flux:main class="w-full max-w-screen-sm mx-auto lg:mt-12">
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
