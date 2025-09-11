@props(['attributes' => ''])

<flux:card class="!border-medium-orange !bg-[var(--card-bg)]" {{ $attributes }}>
    {{ $slot }}
</flux:card>
