@props([
    'modal' => 'confirmation',
    'class_key' => null,
    'action' => null,
    'type' => null,
    'form' => null,
])

<div
    x-dialog
    x-cloak
    x-show="{{ $modal }}"
    class="fixed inset-0 overflow-y-auto z-30"
>
    {{-- Overlay --}}
    <div
        x-dialog:overlay
        x-transition.opacity
        class="fixed inset-0 bg-black/50"
    >
    </div>

    {{-- Panel --}}
    <div class="relative min-h-screen flex items-center justify-center">
        {{ $slot }}
    </div>
</div>
