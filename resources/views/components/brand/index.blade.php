<flux:brand name="The Social Game™" href="{{ route('dashboard') }}" wire:navigate {{ $attributes }}>
    <x-slot name="logo">
        <div class="w-6 h-6 flex items-center justify-center rounded overflow-hidden relative">
            <div class="absolute inset-0 bg-[var(--color-accent)] text-[var(--color-accent-foreground)] flex items-center justify-center">
                <x-icons.gossip class="size-10 mt-3 pl-1 shrink-0" />
            </div>
        </div>
    </x-slot>
</flux:brand>
