@props([
    'color' => 'white',
])

@php
    $color = $color === 'white' ? 'bg-white' : 'bg-black';
@endphp

<div class="{{ $color }}">
    <ul class="space-y-1">
        <li class="text-zinc-50 px-2">text-zinc-50</li>
        <li class="text-zinc-100 px-2">text-zinc-100</li>
        <li class="text-zinc-200 px-2">text-zinc-200</li>
        <li class="text-zinc-300 px-2">text-zinc-300</li>
        <li class="text-zinc-400 px-2">text-zinc-400</li>
        <li class="text-zinc-500 px-2">text-zinc-500</li>
        <li class="text-zinc-600 px-2">text-zinc-600</li>
        <li class="text-zinc-700 px-2">text-zinc-700</li>
        <li class="text-zinc-800 px-2">text-zinc-800</li>
        <li class="text-zinc-900 px-2">text-zinc-900</li>
        <li class="text-zinc-950 px-2">text-zinc-950</li>
    </ul>
</div>
