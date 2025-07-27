@props(['attributes' => ''])

<flux:button
    class="!font-bold !px-4 !py-2 !h-auto !rounded-lg border-0" {{ $attributes }}
    wire:loading.attr="disabled"
>
    {{ $slot }}
</flux:button>
