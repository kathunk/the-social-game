<svg
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="currentColor"
    {{ $attributes->merge(['class' => 'w-5 h-5']) }}
>
    <!-- Original crown path -->
    <path d="M4 16L2 6l5 4 5-7 5 7 5-4-2 10H4zm0 2h16v2H4v-2z" />

    <!-- Background (white) stroke for spacing -->
    <line x1="3" y1="3" x2="21" y2="21" stroke="red" stroke-width="4" stroke-linecap="round" />

    <!-- Foreground slash -->
    <line x1="3" y1="3" x2="21" y2="21" stroke="red" stroke-width="2" stroke-linecap="round" />
</svg>