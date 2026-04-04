<!DOCTYPE html>
<html lang="id" x-data="themeSwitcher()" x-init="init()">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 - Halaman Tidak Ditemukan | {{ config('app.name') }}</title>
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
            <div
                class="w-16 h-16 bg-blue-50 dark:bg-blue-950 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                    stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803 7.5 7.5 0 0016.803 15.803z" />
                </svg>
            </div>

            {{-- Code --}}
            <p class="text-7xl font-light text-blue-500 tracking-tight leading-none mb-2">404</p>

            {{-- Title --}}
            <h1 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                Halaman Tidak Ditemukan
            </h1>

            {{-- Description --}}
            <p class="text-sm text-gray-500 dark:text-gray-400 leading-relaxed mb-8">
                Halaman yang Anda cari tidak ada atau telah dipindahkan.
                Silakan kembali ke kalender.
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
