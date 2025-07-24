<x-layouts.app>
    <div class="flex flex-col gap-4">
        <x-card>
            <flux:heading class="text-green-600">Subscription Successful!</flux:heading>
            <flux:subheading>Thank you for subscribing to <span class="text-md font-semibold">The Social Game</span>.</flux:subheading>

            <div class="mt-4 flex justify-end">
                <flux:button variant="primary" :href="route('dashboard')">
                    Go to Dashboard
                </flux:button>
            </div>
        </x-card>
    </div>
</x-layouts.app>
