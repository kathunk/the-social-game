<div>
    <div class="p-4">
        <div class="hidden lg:block fixed top-2.5 right-12 z-50">
            <x-dark-mode-toggle />
        </div>
        <h1 class="text-2xl font-bold mb-4">Theme Test</h1>

        <div class="grid grid-cols-2 gap-8 mb-8">
            <!-- Default Theme -->
            <div class="base p-6 rounded-lg bg-background">
                <h2 class="text-2xl font-bold mb-2">Default Theme</h2>
                <p class="mb-4">This uses <code>class="default"</code></p>

                <div class="grid grid-cols-2 gap-2">
                    <x-theme.bg />
                </div>

                <button class="mt-4 px-4 py-2 bg-accent text-accent-foreground rounded">
                    Accent Button
                </button>
            </div>

            <!-- Desert Theme -->
            <div class="desert p-6 rounded-lg bg-background dark:bg-black mb-8">
                <h2 class="text-2xl font-bold mb-2">Desert Theme</h2>
                <p class="mb-4">This uses <code>class="desert"</code></p>

                <div class="grid grid-cols-2 gap-2">
                    <x-theme.bg />
                </div>

                <button class="mt-4 px-4 py-2 bg-accent text-accent-foreground rounded">
                    Accent Button
                </button>
            </div>

            <!-- Laravel -->
            <div class="laravel p-6 rounded-lg bg-background">
                <h2 class="text-2xl font-bold mb-2">Laravel Theme</h2>
                <p class="mb-4">This uses <code>class="laravel"</code></p>

                <div class="grid grid-cols-2 gap-2">
                    <x-theme.bg />
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
            <!-- Default -->
            <div class="default p-6 rounded-lg bg-white dark:bg-black">
                <h3 class="text-xl font-bold mb-2">Default Text</h3>
                <div class="grid grid-cols-2 gap-2">
                    <x-theme.text color="white" />
                    <x-theme.text color="black" />
                </div>
            </div>

            <!-- Desert -->
            <div class="desert p-6 rounded-lg bg-background dark:bg-black">
                <h3 class="text-xl font-bold mb-2">Desert Text</h3>

                <div class="grid grid-cols-2 gap-2">
                    <x-theme.text color="white" />
                    <x-theme.text color="black" />
                </div>
            </div>

            <!-- Laravel -->
            <div class="laravel p-6 rounded-lg bg-white dark:bg-black">
                <h3 class="text-xl font-bold mb-2">Laravel Text</h3>
                <div class="grid grid-cols-2 gap-2">
                    <x-theme.text color="white" />
                    <x-theme.text color="black" />
                </div>
            </div>
        </div>
    </div>
</div>
