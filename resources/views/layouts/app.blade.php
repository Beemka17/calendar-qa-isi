<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data="themeSwitcher()" x-init="init()">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <script>
    function themeSwitcher() {
        return {
            isDark: false,
            apply() {
                document.documentElement.classList.toggle('dark', this.isDark);
                localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
            },
            init() {
                const saved = localStorage.getItem('theme');
                this.isDark = saved
                    ? saved === 'dark'
                    : (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches);

                // apply on load
                document.documentElement.classList.add('transition-colors', 'duration-200');
                this.apply();
            },
            toggle() {
                this.isDark = !this.isDark;
                this.apply();
            }
        }
    }
</script>

    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 text-gray-900 dark:bg-gray-950 dark:text-gray-100">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @if (isset($header))
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Page Content -->
            <main>
                @yield('content')
            </main>
        </div>
    </body>
</html>
