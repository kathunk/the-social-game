<div>
    <div class="p-4">
        <h1 class="text-2xl font-bold mb-4">Theme Test - Base & Class Themes</h1>

        <div class="p-10 bg-gradient-to-b from-amber-200 via-amber-300 to-amber-400"></div>


        <div class="grid grid-cols-2 gap-8 mb-8">
            <!-- Laravel -->
            <div class="laravel p-6 rounded-lg bg-background">
                <h2 class="text-2xl font-bold mb-2">Laravel Theme</h2>
                <p class="mb-4">This uses <code>class="laravel"</code></p>

                <div class="grid grid-cols-2 gap-2">
<div class="bg-accent">yo</div>
<div class="bg-accent-content">ho</div>
<div class="bg-accent-foreground">ho</div>
                </div>

                <button class="mt-4 px-4 py-2 bg-accent text-accent-foreground rounded">
                    Accent Button
                </button>
            </div>

            <!-- Pecking Order Theme -->
            <div class="pecking-order p-6 rounded-lg bg-background">
                <h2 class="text-2xl font-bold mb-2">Pecking Order Theme</h2>
                <p class="mb-4">This uses <code>class="pecking-order"</code></p>

                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2 bg-zinc-50 text-zinc-900">bg-zinc-50</div>
                    <div class="p-2 bg-zinc-100 text-zinc-900">bg-zinc-100</div>
                    <div class="p-2 bg-zinc-200 text-zinc-900">bg-zinc-200</div>
                    <div class="p-2 bg-zinc-300 text-zinc-900">bg-zinc-300</div>
                    <div class="p-2 bg-zinc-400 text-zinc-900">bg-zinc-400</div>
                    <div class="p-2 bg-zinc-500 text-zinc-50">bg-zinc-500</div>
                    <div class="p-2 bg-zinc-600 text-zinc-50">bg-zinc-600</div>
                    <div class="p-2 bg-zinc-700 text-zinc-50">bg-zinc-700</div>
                    <div class="p-2 bg-zinc-800 text-zinc-50">bg-zinc-800</div>
                    <div class="p-2 bg-zinc-900 text-zinc-50">bg-zinc-900</div>
                    <div class="p-2 bg-zinc-950 text-zinc-50">bg-zinc-950</div>
                </div>

                <button class="mt-4 px-4 py-2 bg-accent text-accent-foreground rounded">
                    Accent Button
                </button>
            </div>


            <div class="laracon-2025 p-6 rounded-lg bg-background dark:bg-black mb-8">
                <h2 class="text-2xl font-bold mb-2">Laracon 2025 Theme</h2>
                <p class="mb-4">This uses <code>class="laracon-2025"</code></p>

                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2 bg-zinc-50 text-zinc-900">bg-zinc-50</div>
                    <div class="p-2 bg-zinc-100 text-zinc-900">bg-zinc-100</div>
                    <div class="p-2 bg-zinc-200 text-zinc-900">bg-zinc-200</div>
                    <div class="p-2 bg-zinc-300 text-zinc-900">bg-zinc-300</div>
                    <div class="p-2 bg-zinc-400 text-zinc-900">bg-zinc-400</div>
                    <div class="p-2 bg-zinc-500 text-zinc-50">bg-zinc-500</div>
                    <div class="p-2 bg-zinc-600 text-zinc-50">bg-zinc-600</div>
                    <div class="p-2 bg-zinc-700 text-zinc-50">bg-zinc-700</div>
                    <div class="p-2 bg-zinc-800 text-zinc-50">bg-zinc-800</div>
                    <div class="p-2 bg-zinc-900 text-zinc-50">bg-zinc-900</div>
                    <div class="p-2 bg-zinc-950 text-zinc-50">bg-zinc-950</div>
                </div>

                <button class="mt-4 px-4 py-2 bg-accent text-accent-foreground rounded">
                    Accent Button
                </button>
            </div>
        </div>

        <hr class="my-8">

        <!-- Text Color Tests -->
        <h2 class="text-2xl font-bold mb-4">Text Color Tests</h2>

        <div class="grid grid-cols-2 gap-8">
            <div class="pecking-order p-6 rounded-lg bg-white">
                <h3 class="text-xl font-bold mb-2">Pecking Order Text</h3>
                <div class="grid grid-cols-2 gap-2">
                    <x-theme.text color="white" />
                    <x-theme.text color="black" />
                </div>
            </div>

            <div class="laracon-2025 p-6 rounded-lg bg-background dark:bg-black">
                <h3 class="text-xl font-bold mb-2">Laracon 2025 Text</h3>

                <div class="grid grid-cols-2 gap-2">
                    <x-theme.text color="white" />
                    <x-theme.text color="black" />
                </div>
            </div>
        </div>

        <hr class="my-8">

        <!-- Dark Mode Tests -->
        <h2 class="text-2xl font-bold mb-4">Dark Mode Tests</h2>
        <div class="grid grid-cols-2 gap-8">
            <div class="pecking-order dark p-6 rounded-lg bg-background dark:bg-black">
                <h3 class="text-xl font-bold mb-2">Pecking Order + Dark</h3>
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2 dark:bg-zinc-50 dark:text-zinc-900">dark:bg-zinc-50</div>
                    <div class="p-2 dark:bg-zinc-900 dark:text-zinc-50">dark:bg-zinc-900</div>
                </div>
                <button class="mt-4 px-4 py-2 bg-accent text-accent-foreground rounded">
                    Dark Accent Button
                </button>
            </div>

            <div class="laracon-2025 dark p-6 rounded-lg bg-background dark:bg-black">
                <h3 class="text-xl font-bold mb-2">Laracon 2025 + Dark</h3>
                <div class="grid grid-cols-2 gap-2">
                    <div class="p-2 dark:bg-zinc-50 dark:text-zinc-900">dark:bg-zinc-50</div>
                    <div class="p-2 dark:bg-zinc-900 dark:text-zinc-50">dark:bg-zinc-900</div>
                </div>
                <button class="mt-4 px-4 py-2 bg-accent text-accent-foreground rounded">
                    Dark Accent Button
                </button>
            </div>
        </div>

        <div class="mt-8">
            <h2 class="text-2xl font-bold mb-4">Theme Controls</h2>
            <div class="flex space-x-4 mb-4">
                <flux:radio.group x-data variant="segmented">
                    <flux:radio value="default" x-on:click="document.documentElement.removeAttribute('class')">Default</flux:radio>
                    <flux:radio value="pecking-order" x-on:click="document.documentElement.setAttribute('class', 'pecking-order')">Pecking Order</flux:radio>
                    <flux:radio value="laracon-2025" x-on:click="document.documentElement.setAttribute('class', 'laracon-2025')">Laracon 2025</flux:radio>
                </flux:radio.group>
            </div>

            <div class="flex space-x-4">
                <flux:button x-data x-on:click="$flux.dark = ! $flux.dark">
                    Toggle Dark Mode
                </flux:button>
            </div>
        </div>
    </div>
</div>
