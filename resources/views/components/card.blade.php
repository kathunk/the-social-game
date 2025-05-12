<flux:card
    {{ $attributes }}
    class="{{ auth()->user()?->currentPlayer?->team?->forceLight() ? 'light' : '' }}"
>
    {{ $slot }}
</flux:card>
