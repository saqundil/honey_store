@php
    $isRtl = app()->isLocale('ar');
    $metaTitle = trim($__env->yieldContent('title', __('home.meta.title')));
    $metaDescription = trim($__env->yieldContent('meta_description', __('home.meta.description')));
    $metaKeywords = trim($__env->yieldContent('meta_keywords', __('home.meta.keywords')));
    $siteName = __('home.meta.site_name');
    $currentUrl = url()->current();
    $shareImage = asset(__('home.meta.og_image'));
    $faviconUrl = asset('favicon.svg');
    $localeCode = app()->getLocale() === 'ar' ? 'ar_JO' : 'en_US';
    $organizationLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => $siteName,
        'url' => url('/'),
        'logo' => asset('images/honey_logo.png'),
        'description' => $metaDescription,
        'telephone' => __('home.footer.phone_value'),
        'email' => __('home.footer.email'),
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="keywords" content="{{ $metaKeywords }}">
    <meta name="robots" content="index, follow, max-image-preview:large">
    <meta name="author" content="{{ $siteName }}">
    <meta name="application-name" content="{{ $siteName }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:title" content="{{ $metaTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $currentUrl }}">
    <meta property="og:image" content="{{ $shareImage }}">
    <meta property="og:locale" content="{{ $localeCode }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $metaTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $shareImage }}">
    <link rel="canonical" href="{{ $currentUrl }}">
    <link rel="icon" type="image/svg+xml" href="{{ $faviconUrl }}">
    <link rel="shortcut icon" href="{{ $faviconUrl }}">
    <link rel="apple-touch-icon" href="{{ $faviconUrl }}">

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Open+Sans:ital,wght@0,400;0,600;0,700;1,400&family=Open+Sans+Condensed:wght@700&display=swap" rel="stylesheet">

    <style>
        html[dir="rtl"] body {
            font-family: 'Cairo', 'Open Sans', sans-serif;
        }

        html[dir="rtl"] .font-condensed {
            font-family: 'Cairo', 'Open Sans', sans-serif !important;
        }

        html { scroll-behavior: smooth; }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <script type="application/ld+json">{!! json_encode($organizationLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
</head>
<body class="bg-honey-cream font-sans antialiased">

    <x-navbar />

    <main>
        @yield('content')
    </main>

    <x-footer />

</body>
</html>
