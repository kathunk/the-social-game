@props(['attributes' => ''])

{{--
    flux does not allow for conditional attributes inside tags,
    so we disable wire:loading for alpine buttons by switching between two buttons
--}}

@if ($attributes && $attributes->has('@click'))
    <flux:button
        @class([
            '!font-bold !px-4 !py-2 !h-auto !rounded-lg !shadow-none',
            'border-0' => $attributes->has('variant'),
            'border-1' => ! $attributes->has('variant')
        ])
        {{ $attributes }}
    >
        {{ $slot }}
    </flux:button>
@else
    <flux:button
        @class([
            '!font-bold !px-4 !py-2 !h-auto !rounded-lg !shadow-none',
            'border-0' => $attributes->has('variant'),
            'border-1' => ! $attributes->has('variant')
        ])
        {{ $attributes }}
        wire:loading.attr="disabled"
    >
        {{ $slot }}
    </flux:button>
@endif
