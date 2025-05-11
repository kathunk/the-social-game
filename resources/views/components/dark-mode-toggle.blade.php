<flux:dropdown x-data x-cloak>
    <flux:button square size="sm" class="group !cursor-pointer !bg-accent" aria-label="Preferred color scheme">
        <flux:icon.moon x-show="$flux.dark" variant="solid" class="size-8 text-accent-foreground" />
        <flux:icon.sun x-show="! $flux.dark" variant="solid" class="size-10 text-accent-foreground" />

    </flux:button>

    <flux:menu>
        <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">Light</flux:menu.item>
        <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">Dark</flux:menu.item>
        <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">System</flux:menu.item>
    </flux:menu>
</flux:dropdown>
