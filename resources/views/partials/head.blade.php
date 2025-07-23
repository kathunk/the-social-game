<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=fredoka:400,500,600" rel="stylesheet" />

@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
    // Set default appearance to dark mode if no preference has been set yet
    if (!localStorage.getItem('flux:appearance')) {
        localStorage.setItem('flux:appearance', 'dark');
    }
</script>

@fluxAppearance
