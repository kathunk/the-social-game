@props(['class' => null, 'size' => 'lg'])

<div class="{{ $class }}">
    <flux:text class="{{ $class }}">{{ $slot }}</flux:text>
</div>