@props([
    'modal' => 'confirmation',
    'class_key' => null,
    'action' => null,
    'type' => null,
    'form' => null,
    'heading' => null,
    'subheading' => null,
])

<div
    x-cloak
    x-show="{{ $modal }}"
    @keyup.escape.window="{{ $modal }} && ({{ $modal }} = false)"
    class="fixed inset-0 overflow-y-auto z-30"
>
    {{-- Overlay --}}
    <div
        x-transition.opacity
        class="fixed inset-0 bg-black/50"
    ></div>

    {{-- Panel --}}
    <div class="relative min-h-screen flex items-center justify-center">
        {{ $slot }}
    </div>
</div>
