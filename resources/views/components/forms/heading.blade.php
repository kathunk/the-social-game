@props(['class' => null, 'size' => 'lg'])

<div class="{{ $class }}">
    <flux:heading class="font-fredoka-one {{ $class }}" size="{{ $size }}">{{ $slot }}</flux:heading>
</div>