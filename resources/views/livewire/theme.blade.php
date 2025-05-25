<div class="{{ $this->theme }} bg-[var(--color-background)]">
    <div class="p-6 mx-auto">
        <h1 class="text-3xl font-bold mb-6 dark:text-gray-300">Flux UI Components</h1>
        <p class="text-lg text-zinc-600 dark:text-zinc-400">Examples of components with the current theme:</p>
        <flux:select wire:model.live="theme" class="my-4">
            @foreach($this->themes as $theme)
                <flux:select.option>{{ $theme }}</flux:select.option>
            @endforeach
        </flux:select>

        <div id="palette" class="grid grid-cols-4 p-4 border dark:text-zinc-200 text-zinc-800 border-black dark:border-white gap-2">
            <div class="flex gap-4">
                background
                <div class="p-4 bg-[var(--color-background)] border border-dotted border-black dark:border-white">
                </div>
            </div>
            <div class="flex gap-4">
                accent
                <div class="p-4 bg-[var(--color-accent)] border border-dotted border-black dark:border-white">
                </div>
            </div>
            <div class="flex gap-4">
                accent-content
                <div class="p-4 bg-[var(--color-accent-content)] border border-dotted border-black dark:border-white">
                </div>
            </div>
            <div class="flex gap-4">
                accent-foreground
                <div class="p-4 bg-[var(--color-accent-foreground)] border border-dotted border-black dark:border-white">
                </div>
            </div>
        </div>

        <div class="pt-4 grid sm:grid-cols-2 xl:grid-cols-3 gap-4 lg:gap-6 mx-auto sm:max-w-3xl lg:max-w-4xl xl:max-w-none">
            <x-card class="{{ $this->forceLight }}">
                <div class="h-full flex flex-col items-center justify-center gap-4">
                    <flux:button>Button</flux:button>
                    <flux:button variant="primary">Primary</flux:button>
                    <flux:button variant="filled" accent>Filled</flux:button>
                    <flux:button variant="danger">Danger</flux:button>
                    <flux:button variant="ghost">Ghost</flux:button>
                </div>
            </x-card>

            <x-card class="{{ $this->forceLight }}">
                <div class="h-full mx-auto flex items-center justify-center gap-4">
                    <flux:radio.group label="Select your payment method">
                        <flux:radio name="payment" value="cc" label="Credit Card" checked />
                        <flux:radio name="payment" value="paypal" label="Paypal" />
                        <flux:radio name="payment" value="ach" label="Bank transfer" />
                    </flux:radio.group>
                </div>
            </x-card>

            <x-card class="{{ $this->forceLight }}">
                <div class="h-full flex items-center justify-center gap-4 max-w-80 mx-auto">
                    <flux:checkbox.group label="Role">
                        <flux:checkbox
                            name="role"
                            value="administrator"
                            label="Administrator"
                            description="Administrator users can perform any action."
                            checked
                        />
                        <flux:checkbox
                            name="role"
                            value="editor"
                            label="Editor"
                            description="Editor users have the ability to read, create, and update."
                        />
                        <flux:checkbox
                            name="role"
                            value="viewer"
                            label="Viewer"
                            description="Viewer users only have the ability to read. Create, and update are restricted."
                        />
                    </flux:checkbox.group>
                </div>
            </x-card>

            <x-card class="{{ $this->forceLight }}">
                <div class="h-full flex items-center justify-center gap-4 max-w-80 mx-auto">
                    <flux:fieldset>
                        <div class="space-y-4">
                            <flux:switch label="Communication emails" description="Receive emails about your account activity." checked />
                            <flux:separator variant="subtle" />
                            <flux:switch label="Marketing emails" description="Receive emails about new products, features, and more." checked />
                            <flux:separator variant="subtle" />
                            <flux:switch label="Social emails" description="Receive emails for friend requests, follows, and more." />
                            <flux:separator variant="subtle" />
                            <flux:switch label="Security emails" description="Receive emails about your account activity and security." />
                        </div>
                    </flux:fieldset>
                </div>
            </x-card>

            <x-card class="{{ $this->forceLight }}">
                <div class="h-full flex items-center justify-center gap-4 max-w-80 mx-auto">
                    <flux:radio.group label="Shipping" variant="cards" class="flex-col">
                        <flux:radio checked class="w-[14rem]" value="standard" label="Standard" description="4-10 business days" />
                        <flux:radio class="w-[14rem]" value="fast" label="Fast" description="2-5 business days" />
                        <flux:radio class="w-[14rem]" value="next-day" label="Next day" description="1 business day" />
                    </flux:radio.group>
                </div>
            </x-card>

            <x-card class="{{ $this->forceLight }}">
                <div class="h-full flex items-center justify-center gap-4 w-full mx-auto">
                    <flux:sidebar class="w-full bg-zinc-50 dark:bg-zinc-900 border rounded-lg border-zinc-100 dark:border-transparent">
                        <flux:brand href="#" name="Acme Inc." class="px-2">
                            <x-slot name="logo">
                                <div class="size-6 rounded shrink-0 bg-[var(--color-accent)] text-[var(--color-accent-foreground)] flex items-center justify-center"><i class="font-serif font-bold">A</i></div>
                            </x-slot>
                        </flux:brand>

                        <flux:navlist variant="outline">
                            <flux:navlist.item icon="home" href="#" current>Home</flux:navlist.item>
                            <flux:navlist.item icon="inbox" badge="12" href="#">Inbox</flux:navlist.item>
                            <flux:navlist.item icon="document-text" href="#">Documents</flux:navlist.item>
                            <flux:navlist.item icon="calendar" href="#">Calendar</flux:navlist.item>

                            <flux:navlist.group expandable heading="Favorites" class="hidden lg:grid">
                                <flux:navlist.item href="#">Marketing site</flux:navlist.item>
                                <flux:navlist.item href="#">Android app</flux:navlist.item>
                                <flux:navlist.item href="#">Brand guidelines</flux:navlist.item>
                            </flux:navlist.group>
                        </flux:navlist>
                    </flux:sidebar>
                </div>
            </x-card>

            <x-card class="{{ $this->forceLight }}">
                <div>
                    <flux:heading size="lg">Theming Flux</flux:heading>
                    <flux:subheading>Flux uses CSS variables for theming. You can either use these variables directly, or reference them in your CSS file.</flux:subheading>
                    <flux:link href="#" class="text-sm mt-4 !block">Learn more about theming in Flux</flux:link>
                </div>
            </x-card>

            <x-card class="{{ $this->forceLight }}">
                <div class="h-full flex items-center justify-center gap-4 max-w-80 mx-auto">
                    <flux:navbar>
                        <flux:navbar.item href="#" current>Home</flux:navbar.item>
                        <flux:navbar.item href="#">Features</flux:navbar.item>
                        <flux:navbar.item href="#">Pricing</flux:navbar.item>
                        <flux:navbar.item href="#">About</flux:navbar.item>
                    </flux:navbar>
                </div>
            </x-card>

            <x-card class="{{ $this->forceLight }}">
                <div class="h-full flex items-center justify-center gap-4 max-w-80 mx-auto">
                    <flux:tab.group>
                        <flux:tabs>
                            <flux:tab name="profile" icon="user">Profile</flux:tab>
                            <flux:tab name="account" icon="cog-6-tooth">Account</flux:tab>
                            <flux:tab name="billing" icon="banknotes">Billing</flux:tab>
                        </flux:tabs>

                        <flux:tab.panel name="profile" class="!py-0"></flux:tab.panel>
                        <flux:tab.panel name="account" class="!py-0"></flux:tab.panel>
                        <flux:tab.panel name="billing" class="!py-0"></flux:tab.panel>
                    </flux:tab.group>
                </div>
            </x-card>

            <x-card class="{{ $this->forceLight }}">
                <div class="h-full flex items-center justify-center gap-4 max-w-80 mx-auto">
                    <flux:tab.group>
                        <flux:tabs variant="pills">
                            <flux:tab name="profile">Profile</flux:tab>
                            <flux:tab name="account">Account</flux:tab>
                            <flux:tab name="billing">Billing</flux:tab>
                        </flux:tabs>

                        <flux:tab.panel name="profile" class="!py-0"></flux:tab.panel>
                        <flux:tab.panel name="account" class="!py-0"></flux:tab.panel>
                        <flux:tab.panel name="billing" class="!py-0"></flux:tab.panel>
                    </flux:tab.group>
                </div>
            </x-card>

            <x-card class="{{ $this->forceLight }}">
                <div class="h-full flex items-center justify-center gap-4">
                    <flux:button.group>
                        <flux:button icon="power" variant="primary">Shut down</flux:button>
                        <flux:button icon="chevron-down" variant="primary"></flux:button>
                    </flux:button.group>
                </div>
            </x-card>

            <x-card class="{{ $this->forceLight }}">
                <div class="h-full flex items-center justify-center gap-4 max-w-80 mx-auto">
                    <flux:navlist class="min-w-[18rem]">
                        <flux:navlist.item icon="home" href="#" current>Home</flux:navlist.item>
                        <flux:navlist.item icon="inbox" badge="12" href="#">Inbox</flux:navlist.item>
                        <flux:navlist.item icon="document-text" href="#">Documents</flux:navlist.item>
                        <flux:navlist.item icon="calendar" href="#">Calendar</flux:navlist.item>
                    </flux:navlist>
                </div>
            </x-card>

            <div class="col-span-2">
                <x-card class="{{ $this->forceLight }}">
                    <flux:header class="!z-0 !px-4 w-full bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-100 dark:border-transparent">
                        <flux:brand href="#" name="Acme Inc.">
                            <x-slot name="logo">
                                <div class="size-6 rounded shrink-0 bg-[var(--color-accent)] text-[var(--color-accent-foreground)] flex items-center justify-center"><i class="font-serif font-bold">A</i></div>
                            </x-slot>
                        </flux:brand>

                        <flux:navbar class="-mb-px max-lg:hidden">
                            <flux:navbar.item icon="home" href="#" current>Home</flux:navbar.item>
                            <flux:navbar.item icon="inbox" badge="12" href="#">Inbox</flux:navbar.item>
                            <flux:navbar.item icon="document-text" href="#">Documents</flux:navbar.item>
                            <flux:navbar.item icon="calendar" href="#">Calendar</flux:navbar.item>
                        </flux:navbar>
                    </flux:header>
                </x-card>
            </div>
        </div>

        <!-- Show the color palette for reference -->
        <div class="mt-12">
            <h2 class="text-2xl font-semibold border-b pb-2 mb-4 dark:text-gray-300">Base Color Palette</h2>
            <div class="grid grid-cols-1 gap-2">
                <x-theme.bg />
            </div>
        </div>
    </div>
</div>
