@props(['class' => null, 'size' => 'lg'])

<div class="{{ $class }}">
    <flux:heading class="font-heading {{ $class }}" size="{{ $size }}">{{ $slot }}</flux:heading>
</div>