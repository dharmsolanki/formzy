<x-public-layout title="Receipt">

    <x-slot name="head">
        <meta name="robots" content="noindex, nofollow">
    </x-slot>

    <div class="flex justify-end gap-3 mb-4 print:hidden">
        <a href="{{ route('form.public', $submission->form->uuid) }}" class="text-sm text-gray-600 hover:text-gray-900 self-center">&larr; Back to form</a>
        <button type="button" onclick="window.print()"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
            Print / Save as PDF
        </button>
    </div>

    <div class="bg-white border border-gray-200 rounded-xl p-8 print:border-0 print:p-0">

        <div class="flex justify-between items-start border-b border-gray-200 pb-4">
            <div class="flex items-center gap-4">
                @if ($submission->form->user->logoUrl())
                    <img src="{{ $submission->form->user->logoUrl() }}" alt=""
                         class="h-14 w-auto max-w-[140px] object-contain">
                @endif
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Payment Receipt</h1>
                    <p class="text-sm text-gray-500 mt-1">{{ $submission->form->user->company_name }}</p>
                </div>
            </div>
            <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-xs font-medium">PAID</span>
        </div>

        <dl class="mt-4 text-sm divide-y divide-gray-100">
            <div class="flex justify-between py-2">
                <dt class="text-gray-500">Receipt No.</dt>
                <dd class="font-mono text-xs text-gray-900">{{ $submission->uuid }}</dd>
            </div>
            <div class="flex justify-between py-2">
                <dt class="text-gray-500">Date</dt>
                <dd class="text-gray-900">{{ $submission->updated_at->format('d M Y, h:i A') }}</dd>
            </div>
            <div class="flex justify-between py-2">
                <dt class="text-gray-500">For</dt>
                <dd class="text-gray-900">{{ $submission->form->title }}</dd>
            </div>
            <div class="flex justify-between py-2">
                <dt class="text-gray-500">Payment ID</dt>
                <dd class="font-mono text-xs text-gray-900">{{ $submission->razorpay_payment_id }}</dd>
            </div>
            <div class="flex justify-between py-2">
                <dt class="text-gray-500">Amount Paid</dt>
                <dd class="text-lg font-semibold text-gray-900">₹{{ $submission->amount }}</dd>
            </div>
        </dl>

        <h2 class="mt-6 text-sm font-semibold text-gray-900 uppercase tracking-wide">Details</h2>
        <dl class="mt-2 text-sm divide-y divide-gray-100">
            @foreach ($submission->answersForDisplay() as $label => $value)
                <div class="flex justify-between py-2 gap-4">
                    <dt class="text-gray-500">{{ $label }}</dt>
                    <dd class="text-gray-900 text-right">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <p class="mt-8 text-xs text-gray-400 text-center">
            This is a computer-generated receipt and does not require a signature.
        </p>
    </div>

</x-public-layout>