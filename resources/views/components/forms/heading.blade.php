@props(['class' => null, 'size' => 'lg'])

<div class="{{ $class }}">
    <flux:heading class="font-fredoka-one" size="{{ $size }}">{{ $slot }}</flux:heading>
</div>