@props(['time', 'type' => 'ends'])

@php
    $gerund = $type === 'ends' ? 'ending' : 'starting';
@endphp

@if (Carbon\Carbon::parse($time) > now()->addMinutes(60))
    <div class="flex items-baseline space-x-1">
        <span>{{ $type }}</span> <span>{{ Carbon\Carbon::parse($time)->diffForHumans() }}</span>
    </div>
@else
    <div
        wire:ignore
        x-data="{
            endTime: new Date('{{ $time }}'),
            now: new Date(),
            timeLeft: '',
            interval: null,
            tick() {
                let seconds = Math.floor((this.endTime - new Date()) / 1000);
                if (seconds <= 0) {
                    this.timeLeft = '{{ $gerund }}...';
                    return;
                }

                const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                const s = (seconds % 60).toString().padStart(2, '0');
                this.timeLeft = `{{ $type }} in ${m}:${s}`;
            }
        }"
        x-init="
            tick();
            interval = setInterval(() => tick(), 1000);
        "
        @unload="clearInterval(interval)"
        x-text="timeLeft"
    ></div>
@endif