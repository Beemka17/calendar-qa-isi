<!DOCTYPE html>
<html lang="id" x-data="themeSwitcher()" x-init="init()">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses ditolak | {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="font-sans antialiased bg-gray-100 dark:bg-gray-950 text-gray-900 dark:text-gray-100 min-h-screen flex flex-col">

    {{-- Navigation --}}
    <nav class="bg-white dark:bg-gray-900 border-b border-gray-100 dark:border-gray-800 h-14 flex items-center px-6">
        <a href="{{ route('calendar') }}" class="flex items-center gap-2">
            <x-application-logo class="block h-7 w-auto fill-current text-gray-700 dark:text-gray-100" />
            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100 tracking-wide whitespace-nowrap">
                QUALITY DEPT. SCHEDULE ACTIVITY
            </span>
        </a>
    </nav>

    {{-- Error Content --}}
    <div class="flex-1 flex items-center justify-center px-4 py-16">
        <div
            class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-12 text-center max-w-md w-full shadow-sm">

            {{-- Icon --}}
            <div class="w-16 h-16 bg-red-50 dark:bg-red-950 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                </svg>
            </div>

            {{-- Code --}}
            <p class="text-7xl font-light text-red-500 tracking-tight leading-none mb-2">403</p>

            {{-- Title --}}
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                Akses ditolak
            </h1>

            {{-- Description --}}
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed mb-8">
                Anda tidak memiliki izin untuk mengakses halaman ini.
                Jika ini adalah kesalahan, hubungi administrator.
            </p>

            <div class="border-t border-gray-100 dark:border-gray-800 -mx-12 mb-7"></div>

            {{-- Actions --}}
            <div class="flex items-center justify-center gap-3 flex-wrap">
                <a href="{{ route('calendar') }}"
                    class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Kembali ke Kalender
                </a>
            </div>

            <p class="text-xs text-gray-300 dark:text-gray-700 mt-5">Error 404 · Quality Calendar</p>
        </div>
    </div>

</body>

</html>
