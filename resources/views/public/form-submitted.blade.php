<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Submission Received</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto px-4 text-center">
        <div class="bg-white shadow rounded-lg p-8">
            <h1 class="text-xl font-semibold text-gray-900 mb-2">Submission Received</h1>
            <p class="text-gray-600 mb-4">Amount to pay: ₹{{ $submission->amount }}</p>
            <p class="text-sm text-gray-500">Payment integration coming in next step. Reference: {{ $submission->uuid }}</p>
        </div>
    </div>
</body>
</html>