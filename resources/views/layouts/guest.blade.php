<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Formzy') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col justify-center items-center px-4 py-10 bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900">
            <a href="/" class="text-4xl font-bold text-white tracking-tight">
                Form<span class="text-indigo-400">zy</span>
            </a>
            <p class="mt-2 text-sm text-slate-300">Payment collection made simple</p>

            <div class="w-full sm:max-w-md mt-8 px-8 py-8 bg-white shadow-xl rounded-xl">
                {{ $slot }}
            </div>

            <p class="mt-6 text-xs text-slate-400">&copy; {{ date('Y') }} Dravyam Digital</p>
        </div>
    </body>
</html>