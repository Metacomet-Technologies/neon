<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    class="bg-white lg:bg-zinc-100 dark:bg-zinc-900 dark:lg:bg-zinc-950">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Title -->
    <title inertia>{{ config('app.name', 'Laravel') }}</title>

    <!-- Favicon -->
    <link rel="apple-touch-icon" sizes="180x180" href="/image/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/image/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/image/favicon-16x16.png">
    <link rel="icon" type="image/png" sizes="192x192" href="/image/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="512x512" href="/image/android-chrome-512x512.png">
    <link rel="manifest" href="/image/site.webmanifest">
    <link rel="shortcut icon" href="/image/favicon.ico">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&family=Gugi&display=swap" rel="stylesheet">

    @routes()
    @viteReactRefresh
    @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
    @inertiaHead
</head>

<body>
    @inertia
</body>

</html>
