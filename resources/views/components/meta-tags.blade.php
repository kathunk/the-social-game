@props(['title', 'description', 'url'])

<title>{{ $title }}</title>

{{-- Open Graph / Facebook --}}
<meta property="og:type" content="website" />
<meta property="og:url" content="{{ $url }}" />
<meta property="og:title" content="{{ $title }}" />
<meta property="og:description" content="{{ $description }}" />
<meta property="og:image" content="{{ asset('images/OG.png') }}" />
<meta property="og:image:width" content="1200" />
<meta property="og:image:height" content="630" />
<meta property="og:site_name" content="{{ config('app.name') }}" />

{{-- Twitter --}}
<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:url" content="{{ $url }}" />
<meta name="twitter:title" content="{{ $title }}" />
<meta name="twitter:description" content="{{ $description }}" />
<meta name="twitter:image" content="{{ asset('images/OG.png') }}" />

{{-- Additional meta tags --}}
<meta name="description" content="{{ $description }}" />
<meta name="author" content="{{ config('app.name') }}" />

{{-- Canonical URL --}}
<link rel="canonical" href="{{ $url }}" /> 