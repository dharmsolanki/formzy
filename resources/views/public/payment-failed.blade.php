<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Failed</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto px-4 text-center">
        <div class="bg-white shadow rounded-lg p-8">
            <div class="text-red-600 text-4xl mb-4">✗</div>
            <h1 class="text-xl font-semibold text-gray-900 mb-2">Payment Verification Failed</h1>
            <p class="text-gray-600">We could not verify your payment. If money was deducted, please contact support with reference: {{ $submission->uuid }}</p>
        </div>
    </div>
</body>
</html>