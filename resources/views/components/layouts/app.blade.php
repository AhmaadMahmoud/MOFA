@props([
    'title' => null,
    'description' => null,
])
@php
    use App\Support\Locales;

    $locale = app()->getLocale();
    $pageTitle = $title ? $title.' · MOFA' : 'MOFA · '.__('Sourdough & Bagels');
    $pageDescription = $description ?? __('Sourdough, bagels and layered sandwiches, baked fresh by Faten. Order today for tomorrow morning.');
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ Locales::dir() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#e0c3a4">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $pageDescription }}">

    <link rel="canonical" href="{{ url()->current() }}">
    @foreach (Locales::supported() as $alt)
        <link rel="alternate" hreflang="{{ $alt }}" href="{{ Locales::urlFor($alt) }}">
    @endforeach

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="MOFA">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_EG' : 'en_US' }}">
    <meta property="og:image" content="{{ asset('images/brand/mofa-logo.png') }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body class="min-h-dvh pb-[calc(5.5rem+env(safe-area-inset-bottom))] lg:pb-0">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-50 focus:rounded-pill focus:bg-brown focus:px-4 focus:py-2 focus:text-paper">
        {{ __('Skip to content') }}
    </a>

    <x-site.header />

    <main id="main">
        {{ $slot }}
    </main>

    <x-site.footer />

    <x-site.mobile-bar />
</body>
</html>
