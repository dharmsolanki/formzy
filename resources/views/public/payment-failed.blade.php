<x-public-layout title="Payment Failed">

    <x-merchant-brand :merchant="$submission->form->user" />

    <x-card>
        <div class="text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-red-100 text-red-600 flex items-center justify-center text-3xl">✗</div>
            <h1 class="mt-4 text-xl font-semibold text-gray-900">Payment Verification Failed</h1>
            <p class="text-sm text-gray-600 mt-2">We could not verify your payment. If money was deducted, please contact the organizer with the reference below.</p>
        </div>

        <div class="mt-6 border border-gray-200 rounded-lg px-4 py-3 text-sm flex justify-between">
            <span class="text-gray-500">Reference</span>
            <span class="font-mono text-xs text-gray-700">{{ $submission->uuid }}</span>
        </div>
    </x-card>

</x-public-layout>