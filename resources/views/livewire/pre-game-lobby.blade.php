<div wire:poll="checkStatus">
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-4">
            @if ($this->application->status === 'rejected')
                <flux:card>
                    <flux:heading>You were rejected from the game.</flux:heading>
                    <flux:subheading>
                        If you think this was a mistake, please take it up with the man with the bag of cash.
                    </flux:subheading>
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
