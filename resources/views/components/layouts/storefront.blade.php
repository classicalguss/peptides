<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-ink">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Powered Up Peptides' }}</title>
    <meta name="description" content="{{ $description ?? site_text('global.meta_description') }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}?v=1" sizes="48x48">
    <link rel="icon" href="{{ asset('favicon-32x32.png') }}" type="image/png" sizes="32x32">
    <link rel="icon" href="{{ asset('favicon-16x16.png') }}" type="image/png" sizes="16x16">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=archivo:400,700,900,900i|inter:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen overflow-x-clip font-sans antialiased">
    <x-store.entry-gate />
    <x-store.announcement />
    <x-store.nav />

    <main>
        {{ $slot }}
    </main>

    <x-store.footer />
</body>
</html>
