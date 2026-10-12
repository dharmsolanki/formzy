<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name', 'Formzy') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{ $head ?? '' }}
</head>
<body class="font-sans antialiased bg-gray-100 min-h-screen">
    <div class="max-w-xl mx-auto px-4 py-10">

        {{ $slot }}

        <p class="mt-8 text-center text-xs text-gray-400">
            Secure payments powered by Formzy &middot; {{ date('Y') }} Dravyam Digital
        </p>
    </div>
</body>
</html>