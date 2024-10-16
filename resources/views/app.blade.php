<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Icon Butler') }}</title>

        <!-- Meta Tags -->
        <meta name="description" content="Icon Butler is a simple tool that allows you to find the perfect icon for your UI copy. " />

        <!-- Favicon -->
        <link rel="icon" href="/favicon.png" type="image/png">

        <!-- OG Image -->
        <meta property="og:image" content="https://iconbutler.linea.studio/og-image.jpg" />

        <!-- Analytics -->
        <script defer data-domain="iconbutler.linea.studio" src="https://analytics.linea.studio/js/script.js"></script>

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/Pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
