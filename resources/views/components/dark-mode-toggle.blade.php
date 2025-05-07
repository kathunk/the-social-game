<flux:dropdown x-data x-cloak>
    <flux:button variant="subtle" square class="group !cursor-pointer" aria-label="Preferred color scheme">
        <flux:icon.sun x-show="$flux.appearance === 'light'" variant="solid" class="size-10 text-amber-100 dark:text-white" />
        <flux:icon.moon x-show="$flux.appearance === 'dark'" variant="solid" class="size-8 text-zinc-500 dark:text-white" />
        <flux:icon.moon x-show="$flux.appearance === 'system' && $flux.dark" variant="solid" class="size-10 text-amber-100 dark:text-white" />
        <flux:icon.sun x-show="$flux.appearance === 'system' && ! $flux.dark" variant="solid" class="size-8 text-zinc-500 dark:text-white" />
    </flux:button>

    <flux:menu>
        <flux:menu.item icon="sun" x-on:click="$flux.appearance = 'light'">Light</flux:menu.item>
        <flux:menu.item icon="moon" x-on:click="$flux.appearance = 'dark'">Dark</flux:menu.item>
        <flux:menu.item icon="computer-desktop" x-on:click="$flux.appearance = 'system'">System</flux:menu.item>
    </flux:menu>
</flux:dropdown>
