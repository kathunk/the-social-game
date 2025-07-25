<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<x-meta-tags 
    :title="$title"
    description="Play The Social Game."
    :url="request()->url()"
/>

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=fredoka:400,500,600" rel="stylesheet" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fredoka+One&display=swap" rel="stylesheet">

@vite(['resources/css/app.css', 'resources/js/app.js'])

<script>
    // Set default appearance to dark mode if no preference has been set yet
    if (!localStorage.getItem('flux:appearance')) {
        localStorage.setItem('flux:appearance', 'dark');
    }
</script>

@fluxAppearance
