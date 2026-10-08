<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto px-4 text-center">
        <div class="bg-white shadow rounded-lg p-8">
            <div class="text-5xl font-bold text-gray-300 mb-2">@yield('code')</div>
            <h1 class="text-xl font-semibold text-gray-900 mb-2">@yield('heading')</h1>
            <p class="text-gray-600 mb-6">@yield('message')</p>
            <a href="{{ url('/') }}" class="inline-block bg-gray-800 text-white px-4 py-2 rounded-md text-sm">Go to Home</a>
        </div>
    </div>
</body>
</html>