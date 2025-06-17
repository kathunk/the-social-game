<flux:brand name="The Social Game™" href="{{ route('dashboard') }}" wire:navigate {{ $attributes }}>
    <x-slot name="logo">
        <x-icons.speech-bubbles class="bg-[var(--color-accent)] text-[var(--color-accent-foreground)]" />
    </x-slot>
</flux:brand>
