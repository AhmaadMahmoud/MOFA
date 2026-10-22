@props([
    'title' => null,
    'description' => null,
    // Checkout and order pages carry their own bottom action bar.
    'mobileBar' => true,
    'noindex' => false,
    // [locale => url] when the page's other-language URL isn't just the same route (signed links).
    'alternates' => null,
    // Pages with Livewire components load Livewire's bundle (which brings its own Alpine).
    'livewire' => false,
])
@php
    use App\Support\Locales;

    $locale = app()->getLocale();
    $pageTitle = $title ? $title.' · MOFA' : 'MOFA · '.__('Sourdough & Bagels');
    $alternates ??= collect(Locales::supported())->mapWithKeys(fn ($l) => [$l => Locales::urlFor($l)])->all();
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
    @if ($noindex)
        <meta name="robots" content="noindex">
    @endif

    <link rel="canonical" href="{{ url()->current() }}">
    @unless ($noindex)
        @foreach ($alternates as $alt => $altUrl)
            <link rel="alternate" hreflang="{{ $alt }}" href="{{ $altUrl }}">
        @endforeach
    @endunless

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="MOFA">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $pageDescription }}">
    <meta property="og:locale" content="{{ $locale === 'ar' ? 'ar_EG' : 'en_US' }}">
    <meta property="og:image" content="{{ asset('images/brand/mofa-logo.png') }}">

    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('images/brand/apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', $livewire ? 'resources/js/livewire.js' : 'resources/js/app.js'])
    @if ($livewire)
        @livewireStyles
    @endif
    {{ $head ?? '' }}
</head>
<body @class(['min-h-dvh lg:pb-0', 'pb-[calc(5.5rem+env(safe-area-inset-bottom))]' => $mobileBar])>
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:start-4 focus:top-4 focus:z-50 focus:rounded-pill focus:bg-brown focus:px-4 focus:py-2 focus:text-paper">
        {{ __('Skip to content') }}
    </a>

    <x-site.header :switch-url="$alternates[Locales::other()] ?? null" />

    <main id="main">
        {{ $slot }}
    </main>

    <x-site.footer />

    @if ($mobileBar)
        {{-- A page can bring its own bottom bar (the item page: price + add). --}}
        @isset($bar)
            {{ $bar }}
        @else
            <x-site.mobile-bar />
        @endisset
    @endif

    <x-site.cart-sheet />

    @if ($livewire)
        @livewireScriptConfig
    @endif
</body>
</html>
