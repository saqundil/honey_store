@php($isRtl = app()->isLocale('ar'))
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('home.meta.title'))</title>

    {{-- Google Fonts --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;700&family=Open+Sans:ital,wght@0,400;0,700;1,400&family=Open+Sans+Condensed:wght@700&display=swap" rel="stylesheet">

    <style>
        html[dir="rtl"] body {
            font-family: 'Cairo', 'Open Sans', sans-serif;
        }

        html[dir="rtl"] .font-condensed {
            font-family: 'Cairo', 'Open Sans', sans-serif !important;
        }
    </style>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-honey-cream font-sans antialiased">

    <x-navbar />

    <main>
        @yield('content')
    </main>

    <x-footer />

</body>
</html>
