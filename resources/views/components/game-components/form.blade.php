@props(['form', 'type', 'class_key'])

<flux:card>
    <div class="flex flex-col space-y-4">
        @if ($type === 'challenge')
            <div class="flex space-x-2 items-baseline">
                <flux:heading size="lg">Challenge</flux:heading>
            
                @if ($this->challenge->ends_at->isFuture())
                    <flux:text variant="subtle" class="flex items-baseline gap-1">
                        ends in
                        <x-game-components.countdown-timer :ends_at="$this->challenge->ends_at->toIsoString()" />
                    </flux:text>
                @else
                    <flux:text variant="subtle">ending...</flux:text>
                @endif
            </div>
        @endif
        @foreach ($form['elements'] as $element)
            @switch($element['type'])
                @case('title')
                    <flux:heading>{{ $element['text'] }}</flux:heading>
                    @break
                @case('subtitle')
                    <flux:subheading>{{ $element['text'] }}</flux:subheading>
                    @break
                @case('image')
                    <img src="{{ $element['url'] }}" alt="{{ $element['alt'] }}" class="w-full h-auto rounded-lg my-4" />
                    @break
                @case('message')
                    <flux:text class="mt-1">{{ $element['text'] }}</flux:text>
                    @break
                @case('divider')
                    <flux:separator class="my-4" />
                    @break
                @case('table')
                    <flux:table :rows="$element['rows']" />
                    @break
                @case('input')
                    <flux:input
                        label="{{ $element['label']}}"
                        placeholder="{{$element['placeholder']}}"
                        wire:model="round_properties.{{ $class_key }}.{{ $element['property_name']}}"
                    />
                    @break
                @case('button_group')
                    <div class="flex space-x-2 mt-4 justify-end">
                        @foreach ($element['buttons'] as $btn)
                            <flux:button
                                variant="primary"
                                wire:click="callClassAction('{{ $btn['action'] }}', '{{ $type }}', '{{ $class_key }}')"
                            >
                                {{ $btn['label'] }}
                            </flux:button>
                        @endforeach
                    </div>
                    @break
                @case('select')
                    <flux:select
                        label="{{ $element['label'] }}"
                        wire:model="round_properties.{{ $class_key }}.{{ $element['property_name']}}"
                    >
                    @isset($element['placeholder'])
                        <flux:select.option value="" selected class="placeholder">{{ $element['placeholder'] }}</flux:select.option>
                    @endisset
                        @foreach($element['options'] as $key => $value)
                            <flux:select.option value="{{ $key }}">{{ $value }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    @break
            @endswitch
        @endforeach
    </div>
</flux:card>
