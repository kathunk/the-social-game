@props(['modal' => 'confirmation'])

<button
    @click="{{ $modal }} = ! {{ $modal }}"
    class="absolute top-1 right-2 z-30 px-2"
>
x
</button>
