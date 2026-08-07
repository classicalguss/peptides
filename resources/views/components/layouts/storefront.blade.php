<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="bg-ink">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Powered Up Peptides' }}</title>
    <meta name="description" content="{{ $description ?? 'Third-party tested research peptides and stack protocols. 99%+ purity, published COA for every batch.' }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=archivo:400,700,900,900i|inter:400,500,600,700,800" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen font-sans antialiased">
    <x-store.announcement />
    <x-store.nav />

    <main>
        {{ $slot }}
    </main>

    <x-store.footer />
</body>
</html>
