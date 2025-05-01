@props(['size' => 200, 'url' => null])

{!! QrCode::size($size)->generate($url ?? url()->current()) !!}
