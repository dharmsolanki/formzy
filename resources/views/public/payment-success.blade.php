<x-public-layout title="Payment Successful">

    <div class="text-center mb-6">
        <div class="text-2xl font-bold text-gray-900 tracking-tight">
            Form<span class="text-indigo-600">zy</span>
        </div>
    </div>

    <x-card>
        <div class="text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-3xl">✓</div>
            <h1 class="mt-4 text-xl font-semibold text-gray-900">Payment Successful</h1>
            <p class="text-sm text-gray-500 mt-1">{{ $submission->form->title }}</p>
        </div>

        <div class="mt-6 border border-gray-200 rounded-lg divide-y divide-gray-100 text-sm">
            <div class="flex justify-between px-4 py-3">
                <span class="text-gray-500">Amount Paid</span>
                <span class="font-semibold text-gray-900">₹{{ $submission->amount }}</span>
            </div>
            <div class="flex justify-between px-4 py-3">
                <span class="text-gray-500">Date</span>
                <span class="text-gray-900">{{ $submission->updated_at->format('d M Y, h:i A') }}</span>
            </div>
            <div class="flex justify-between px-4 py-3">
                <span class="text-gray-500">Payment ID</span>
                <span class="font-mono text-xs text-gray-700">{{ $submission->razorpay_payment_id }}</span>
            </div>
            <div class="flex justify-between px-4 py-3">
                <span class="text-gray-500">Reference</span>
                <span class="font-mono text-xs text-gray-700">{{ $submission->uuid }}</span>
            </div>
        </div>

        @if ($submission->form->success_message)
            <div class="mt-6 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm whitespace-pre-line">{{ $submission->form->success_message }}</div>
        @endif

        <p class="mt-6 text-center text-xs text-gray-400">Please save the reference above for future queries.</p>
    </x-card>

</x-public-layout>