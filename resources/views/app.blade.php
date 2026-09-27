<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="restivo">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title inertia>{{ config('app.name', 'Restivo') }}</title>

        <link rel="preconnect" href="https://cdnjs.cloudflare.com">
        <link
            rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
            referrerpolicy="no-referrer"
        />

        @viteReactRefresh
        @vite(['resources/js/index.css', 'resources/js/app.tsx'])
        @inertiaHead

        <script>
            (function () {
                try {
                    var theme = localStorage.getItem('theme');
                    if (theme === 'restivo' || theme === 'restivo-dark') {
                        document.documentElement.setAttribute('data-theme', theme);
                    }
                } catch (e) {}
            })();
        </script>
    </head>
    <body class="min-h-screen bg-base-200 text-base-content antialiased">
        @inertia
    </body>
</html>
