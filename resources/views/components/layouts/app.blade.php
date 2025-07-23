<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main class="w-full max-w-screen-sm mx-auto lg:mt-12 bg-[#FFE6D8]">
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
