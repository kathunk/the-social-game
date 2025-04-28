<div wire:poll="checkStatus">
    @if ($this->game->status === 'upcoming')
        <div class="mx-auto w-full mb-4 text-center">
            <flux:text>Starts {{ $this->game->starts_at->diffForHumans() }}</flux:text>
        </div>
    @endif

    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-4">
            @if ($this->application->status === 'rejected')
                <flux:card>
                    <flux:heading>You were rejected from the game.</flux:heading>
                </flux:card>
            @else
                <flux:card>
                    <div class="flex flex-col gap-2">
                        {!! $this->description !!}
                    </div>
                </flux:card>
            @endif
        </div>
    </div>
</div>

{{-- @todo --}}
{{-- guest visits page --}}
{{-- auth'd user with no application --}}
{{-- rejected user --}}
{{-- accepted user --}}
{{-- game needs reschedule --}}
{{-- admin can move start time --}}
{{-- admin can add admins --}}
{{-- admin can remove admins --}}
{{-- cancel game? --}}
{{-- change game template? feels like that's just a cancel --}}
{{-- toggle ability  --}}