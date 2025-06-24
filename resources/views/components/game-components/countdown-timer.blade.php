@props(['ends_at'])

@if ($ends_at > now()->addMinutes(60))
    <div>
        ends {{ Carbon\Carbon::parse($ends_at)->diffForHumans() }}
    </div>
@else
    <div
        wire:ignore
        x-data="{
            endTime: new Date('{{ $ends_at }}'),
            now: new Date(),
            timeLeft: '',
            interval: null,
            tick() {
                let seconds = Math.floor((this.endTime - new Date()) / 1000);
                if (seconds <= 0) {
                    this.timeLeft = 'ending...';
                    return;
                }

                const m = Math.floor(seconds / 60).toString().padStart(2, '0');
                const s = (seconds % 60).toString().padStart(2, '0');
                this.timeLeft = `ends in ${m}:${s}`;
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