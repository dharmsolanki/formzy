<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Successful</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto px-4 text-center">
        <div class="bg-white shadow rounded-lg p-8">
            <div class="text-green-600 text-4xl mb-4">✓</div>
            <h1 class="text-xl font-semibold text-gray-900 mb-2">Payment Successful</h1>
            <p class="text-gray-600 mb-1">Amount Paid: ₹{{ $submission->amount }}</p>
            @if ($submission->form->success_message)
                <p class="text-gray-700 my-4 whitespace-pre-line">{{ $submission->form->success_message }}</p>
            @endif
            <p class="text-sm text-gray-500">Reference: {{ $submission->uuid }}</p>
        </div>
    </div>
</body>

</html>
