<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Transaction') }}
            </h2>
            <a href="{{ route('reports.transactions') }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to Transaction Report</a>
        </div>
    </x-slot>

    @php $rzp = session('rzp'); @endphp

    <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-4xl">

        @if (session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->has('razorpay'))
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                {{ $errors->first('razorpay') }}
            </div>
        @endif

        {{-- Summary --}}
        <x-card title="Summary" class="mb-6">
            <dl class="text-sm divide-y divide-gray-100">
                <div class="flex justify-between py-2"><dt class="text-gray-500">Merchant</dt><dd class="text-gray-900">{{ $submission->form->user->company_name }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Form</dt><dd class="text-gray-900">{{ $submission->form->title }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Amount</dt><dd class="font-semibold text-gray-900">₹{{ $submission->amount }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Status (Formzy)</dt><dd><x-badge :status="$submission->payment_status" /></dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Created</dt><dd class="text-gray-900">{{ $submission->created_at->format('d M Y, h:i A') }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Last updated</dt><dd class="text-gray-900">{{ $submission->updated_at->format('d M Y, h:i A') }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Order ID</dt><dd class="font-mono text-xs text-gray-900">{{ $submission->razorpay_order_id ?: '-' }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Payment ID</dt><dd class="font-mono text-xs text-gray-900">{{ $submission->razorpay_payment_id ?: '-' }}</dd></div>
                <div class="flex justify-between py-2"><dt class="text-gray-500">Reference</dt><dd class="font-mono text-xs text-gray-900">{{ $submission->uuid }}</dd></div>
            </dl>
        </x-card>

        {{-- Razorpay check --}}
        <x-card title="Razorpay Check" class="mb-6">
            <x-slot name="action">
                <form method="POST" action="{{ route('reports.transactions.check', $submission->uuid) }}">
                    @csrf
                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium whitespace-nowrap">
                        Check with Razorpay
                    </button>
                </form>
            </x-slot>

            @if ($rzp)
                @php
                    $styles = [
                        'match'    => 'bg-green-50 border-green-200 text-green-800',
                        'mismatch' => 'bg-red-50 border-red-200 text-red-800',
                        'review'   => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                    ];
                @endphp

                <div class="border rounded-lg px-4 py-3 text-sm {{ $styles[$rzp['verdict']] ?? $styles['review'] }}">
                    <div class="font-semibold">{{ $rzp['message'] }}</div>
                    <div class="mt-1 text-xs">Razorpay: {{ $rzp['state'] }} &middot; {{ $rzp['mode'] }} mode &middot; checked {{ $rzp['checked_at'] }}</div>
                </div>

                @if ($rzp['amount_warning'])
                    <div class="mt-3 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg text-sm">
                        Captured amount on Razorpay does not match the amount in Formzy. Review manually.
                    </div>
                @endif

                @if ($rzp['verdict'] === 'mismatch')
                    @if ($rzp['can_sync'])
                        <form method="POST" action="{{ route('reports.transactions.sync', $submission->uuid) }}"
                              onsubmit="return confirm('Mark this payment as paid? Razorpay shows {{ $rzp['sync_payment_id'] }} captured for ₹{{ $submission->amount }}.');"
                              class="mt-3">
                            @csrf
                            <button type="submit"
                                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                                Sync from Razorpay (mark as paid)
                            </button>
                            <p class="text-xs text-gray-500 mt-1">Razorpay is checked again before anything is changed. Every sync is logged.</p>
                        </form>
                    @else
                        <div class="mt-3 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg text-sm">
                            Cannot sync automatically: {{ $rzp['sync_reason'] }}
                        </div>
                    @endif
                @endif

                @if ($rzp['order'])
                    <dl class="mt-4 text-sm divide-y divide-gray-100">
                        <div class="flex justify-between py-2"><dt class="text-gray-500">Order status</dt><dd class="text-gray-900">{{ $rzp['order']['status'] }}</dd></div>
                        <div class="flex justify-between py-2"><dt class="text-gray-500">Order amount</dt><dd class="text-gray-900">₹{{ number_format($rzp['order']['amount'] / 100, 2) }}</dd></div>
                        <div class="flex justify-between py-2"><dt class="text-gray-500">Amount paid</dt><dd class="text-gray-900">₹{{ number_format($rzp['order']['amount_paid'] / 100, 2) }}</dd></div>
                        <div class="flex justify-between py-2"><dt class="text-gray-500">Attempts</dt><dd class="text-gray-900">{{ $rzp['order']['attempts'] }}</dd></div>
                    </dl>
                @endif

                <div class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Payment ID</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($rzp['payments'] as $p)
                                <tr>
                                    <td class="px-4 py-2 font-mono text-xs text-gray-700">{{ $p['id'] }}</td>
                                    <td class="px-4 py-2 text-gray-900">{{ $p['status'] }}</td>
                                    <td class="px-4 py-2 text-gray-700">{{ $p['method'] ?: '-' }}</td>
                                    <td class="px-4 py-2 text-gray-900">₹{{ number_format($p['amount'] / 100, 2) }}</td>
                                    <td class="px-4 py-2 text-gray-600 whitespace-nowrap">{{ $p['created_at'] }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-4 text-center text-gray-500">No payment attempts on this order.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-gray-500">
                    Asks Razorpay (using this form's own key) what really happened. Read-only, nothing is changed in Formzy.
                </p>
            @endif
        </x-card>

        {{-- Customer data --}}
        <x-card title="Customer Data" class="mb-6">
            <dl class="text-sm divide-y divide-gray-100">
                @forelse ($submission->answersForDisplay() as $label => $value)
                    <div class="flex justify-between py-2 gap-4">
                        <dt class="text-gray-500">{{ $label }}</dt>
                        <dd class="text-gray-900 text-right">{{ $value }}</dd>
                    </div>
                @empty
                    <div class="py-2 text-gray-500">No data.</div>
                @endforelse
            </dl>
        </x-card>

        {{-- Audit trail --}}
        <x-card title="Activity (last 10)">
            <ul class="text-sm divide-y divide-gray-100 -my-2">
                @foreach ($history as $log)
                    <li class="flex justify-between py-2 gap-4">
                        <span class="text-gray-900">{{ $log->action }} <span class="text-gray-500">by {{ optional($log->user)->name ?? 'unknown' }}</span></span>
                        <span class="text-gray-500 whitespace-nowrap">{{ $log->created_at->format('d M Y, h:i A') }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>
    </div>
</x-app-layout>