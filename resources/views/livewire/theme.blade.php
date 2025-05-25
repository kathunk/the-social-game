<div @class([
    'xl:h-screen',
    $this->theme .' bg-[var(--color-background)]' => $this->theme !== 'default',
    'bg-white dark:bg-zinc-800'=> $this->theme === 'default',
])>
    <div class="p-6 mx-auto">
        <h1 class="text-3xl font-bold mb-6 text-zinc-500">Flux UI Components</h1>
        <p class="text-lg text-zinc-500">Examples of components with the current theme:</p>
        <flux:select wire:model.live="theme" class="my-4 !bg-[var(--color-accent)] !text-[var(--color-accent-foreground)]">
            @foreach($this->themes as $theme)
                <flux:select.option>{{ $theme }}</flux:select.option>
            @endforeach
        </flux:select>

        <div id="palette" class="flex p-4 border border-dotted text-zinc-500 border-black dark:border-white justify-around grow h-full w-full gap-x-4">
            <div class="flex flex-col sm:flex-row justify-between sm:justify-normal gap-2 sm:gap-4 items-center w-1/4">
                background
                <div class="p-4 bg-[var(--color-background)] border border-dotted border-black dark:border-white">
                </div>
            </div>
            <div class="flex flex-col sm:flex-row justify-between sm:justify-normal sm:gap-4 items-center w-1/4">
                accent
                <div class="p-4 bg-[var(--color-accent)] border border-dotted border-black dark:border-white">
                </div>
            </div>
            <div class="flex flex-col sm:flex-row justify-between sm:justify-normal sm:gap-4 items-center w-1/4">
                <span class="hidden sm:block">accent-content</span>
                <span class="block sm:hidden">content</span>
                <div class="p-4 bg-[var(--color-accent-content)] border border-dotted border-black dark:border-white">
                </div>
            </div>
            <div class="flex flex-col sm:flex-row justify-between sm:justify-normal sm:gap-4 items-center w-1/4">
                <span class="hidden sm:block">accent-foreground</span>
                <span class="block sm:hidden">foreground</span>
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

            {{-- Headings and Link --}}
            <x-card class="{{ $this->forceLight }}">
                <div class="h-full flex flex-col items-center justify-center gap-4 w-full mx-auto">
                    <flux:heading size="lg">Theming Flux</flux:heading>
                    <flux:subheading>Flux uses CSS variables for theming. You can either use these variables directly, or reference them in your CSS file.</flux:subheading>
                    <flux:link href="#" class="text-sm mt-4 !block">Learn more about theming in Flux</flux:link>
                </div>
            </x-card>

            {{-- Sidebar --}}
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

            {{-- <x-theme.extra :light="$this->forceLight" /> --}}
        </div>
        {{-- <x-theme.bg /> --}}
    </div>
</div>
