@props([
    'scoreHistoryEntries' => [],
])

@if (count($this->scoreHistoryEntries) > 0)
        <flux:table class="*:!border-0 -ml-1">
        <flux:table.columns class="**:!pb-0">
            <div class="text-faded-gray text-tiny md:text-xxs font-bold mt-2.5">
                EVENT TIMELINE
            </div>
        </flux:table.columns>
        <flux:table.rows>
            @foreach ($this->scoreHistoryEntries as $entry)
                <flux:table.row @class([
                    '*:!py-2 **:!text-xs md:**:!text-sm',
                    '!bg-gray-50' => $entry['is_hidden'],
                ])>
                    <flux:table.cell class="flex items-start !px-1">
                        {{ $entry['icon'] ?? '🐛' }}
                    </flux:table.cell>
                    <flux:table.cell>
                        <div class="flex flex-col -ml-2">
                            @if ($entry['points'] > -1)
                                <flux:heading class="whitespace-normal break-words font-bold text-dark-cyan">{{ $entry['description'] }}{{ $entry['is_hidden'] ? ' (Hidden)' : '' }}</flux:heading>
                            @else
                                <flux:heading class="whitespace-normal break-words font-bold text-red-500">{{ $entry['description'] }}{{ $entry['is_hidden'] ? ' (Hidden)' : '' }}</flux:heading>
                            @endif
                            <flux:text class="mt-2 text-faded-gray">{{ Carbon\Carbon::parse($entry['timestamp'])->diffForHumans() }}</flux:text>
                        </div>
                    </flux:table.cell>
                    <flux:table.cell class="flex items-start">
                        <div>
                            @if ($entry['points'] > -1)
                                <flux:heading class="text-dark-cyan">
                                    {{ $entry['points'] > 0 ? '+' : '' }}{{ $entry['points'] }}
                                </flux:heading>
                            @else
                                <flux:heading class="text-red-500">{{ $entry['points'] }}</flux:heading>
                            @endif
                        </div>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
@else
    <flux:subheading>No score history yet</flux:subheading>
@endif
