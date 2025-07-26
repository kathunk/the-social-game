@props(['attributes' => ''])

<flux:button class="!font-bold !px-4 !py-2 !h-auto !rounded-lg border-0" {{ $attributes }}>
    {{ $slot }}
</flux:button>
