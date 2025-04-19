<flux:card>
    @foreach ($challengeComponent['elements'] as $element)
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
            @case('divider')
                <flux:separator class="my-4" />
                @break
            @case('table')
                <flux:table :rows="$element['rows']" />
                @break
            @case('button_group')
                <div class="flex space-x-2 mt-4 justify-end">
                    @foreach ($element['buttons'] as $btn)
                        <button
                            wire:click="callAction('{{ $btn['action'] }}')"
                            class="px-4 py-2 bg-blue-600 text-white rounded"
                        >
                            {{ $btn['label'] }}
                        </button>
                    @endforeach
                </div>
                @break
        @endswitch
    @endforeach
</flux:card>
