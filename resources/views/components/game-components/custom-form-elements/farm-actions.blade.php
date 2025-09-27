@props(['element'])

@php
    
@endphp

<div>
    <div class="mt-4">
        <x-heading>{{ chr(65 + $element['player_space']['x-index']) }}{{ $element['player_space']['y-index'] + 1 }}: {{ $element['player_space']['type'] }}</x-heading>
        <x-subheading>Players: {{ implode(', ', collect($element['player_space']['player_ids'])->map(fn ($id) => $this->player->id === $id ? 'You' : App\Models\Player::find($id)->name)->toArray()) }}</x-subheading>
    </div>

    <x-heading>Actions: {{ $element['actions']['actions'] }} / {{ $element['actions']['limit'] }}</x-heading>
</div>