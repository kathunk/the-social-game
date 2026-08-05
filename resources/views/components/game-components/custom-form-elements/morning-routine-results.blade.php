@props(['element'])

@php
    $results = $element['results'];
    $typeIcons = [
        'reward' => '🌅',
        'effect' => '✨',
        'bust' => '🚨',
        'exit' => '🏃',
        'stranded' => '😴',
    ];
@endphp

<div class="space-y-4">
    <div class="text-center">
        <h3 class="text-lg font-bold text-gray-800">How the morning went</h3>
        <p class="text-xs text-gray-500">Everyone's routine, busts, and bonuses.</p>
    </div>

    @foreach ($results as $result)
        <div class="rounded-lg border-2 p-4 space-y-3
            {{ $result['is_current_player'] ? 'border-blue-300 bg-blue-50/50' : 'border-gray-200 bg-white' }}">

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-sm">{{ $result['name'] }}</span>
                    @if ($result['is_current_player'])
                        <span class="text-xs text-blue-500">(you)</span>
                    @endif
                    @if ($result['exit_position'] !== null)
                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xxs font-medium text-green-700">
                            🚪 #{{ $result['exit_position'] }} out the door
                        </span>
                    @elseif ($result['stranded_in'] !== null)
                        <span class="rounded-full bg-red-100 px-2 py-0.5 text-xxs font-medium text-red-700">
                            😴 caught in the {{ $result['stranded_in'] }}
                        </span>
                    @endif
                </div>
                <span class="rounded-full px-3 py-1 text-sm font-bold
                    {{ $result['total'] >= 0 ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                    {{ $result['total'] >= 0 ? '+' : '' }}{{ $result['total'] }}
                </span>
            </div>

            @if (count($result['inventory']) > 0)
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($result['inventory'] as $item)
                        <span
                            class="inline-flex items-center gap-1 rounded-full bg-yellow-50 border border-yellow-200 px-2.5 py-0.5 text-xxs font-medium text-yellow-800"
                            @if ($item['effect_description']) title="{{ $item['effect_description'] }}" @endif
                        >
                            {{ $item['name'] }}
                            <span class="text-yellow-600">({{ $item['room'] }})</span>
                        </span>
                    @endforeach
                </div>
            @else
                <p class="text-xs text-gray-400 italic">Didn't pick up a thing all morning.</p>
            @endif

            @if (count($result['ledger']) > 0)
                <div class="border-t border-gray-100 pt-2 space-y-1">
                    @foreach ($result['ledger'] as $entry)
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-gray-600">
                                {{ $typeIcons[$entry['type']] ?? '🌅' }} {{ $entry['label'] }}
                            </span>
                            <span class="font-semibold {{ $entry['points'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $entry['points'] >= 0 ? '+' : '' }}{{ $entry['points'] }}
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>
