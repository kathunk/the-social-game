<div class="flex flex-col gap-4">
    <flux:card>
        <flux:heading size="lg" level="1">Subscribe</flux:heading>
        <flux:subheading>Get full access to create new games with our yearly subscription.</flux:subheading>

        <flux:separator class="my-4" />

        <div class="flex justify-between p-4">
            <div class="self-center rounded-lg">
                <div>
                    <flux:heading class="text-zinc-300 font-thin" level="2">Yearly Subscription</flux:heading>
                    <flux:text class="text-white text-xl font-bold">$9.99</flux:heading>
                </div>
            </div>

            <div class="flex self-end items-center gap-2">
                <div class="text-right">
                    <flux:text class="text-xs">Automatic renewal</flux:text>
                    <flux:text class="text-xs">Cancel anytime</flux:text>
                </div>
                <flux:button variant="primary" wire:click="checkout">
                    Subscribe
                </flux:button>
            </div>
        </div>
    </flux:card>
</div>
