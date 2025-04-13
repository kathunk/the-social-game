<div>
    <flux:card>
        <flux:heading class="mb-4">Pending Players</flux:heading>

        <flux:select wire:model="selected_application_id" variant="listbox" searchable placeholder="Choose player...">
            @foreach ($this->newApplications as $application)
                <flux:select.option :value="(string) $application->id">
                    {{ $application->user->name }} ({{ $application->user->email }})
                    @if ($this->acceptedUserNames->contains($application->user->name))
                        <flux:badge class="ml-2" size="sm" color="red">Duplicate Name</flux:badge>
                    @endif
                </flux:select.option>
            @endforeach
        </flux:select>
        <div class="flex justify-end mt-4 gap-2">
            <flux:button variant="primary" wire:click="approveUser">Approve</flux:button>
            <flux:button variant="danger" wire:click="rejectUser">Reject</flux:button>
        </div>
    </flux:card>

    <flux:toast />
</div>