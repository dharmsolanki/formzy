<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Merchant Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">My Forms</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['forms'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Paid Payments</div>
                    <div class="text-2xl font-semibold text-green-600">{{ $stats['paid_count'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Pending Payments</div>
                    <div class="text-2xl font-semibold text-yellow-600">{{ $stats['pending_count'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Total Collected</div>
                    <div class="text-2xl font-semibold text-gray-900">₹{{ number_format($stats['paid_total'], 2) }}</div>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-8">
                <h3 class="font-semibold text-gray-800 mb-4">My Forms</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Pending</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Collected</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Link</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($forms as $form)
                                <tr>
                                    <td class="px-4 py-2 text-sm">{{ $form->title }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        @if ($form->amount_type === 'fixed')
                                            ₹{{ $form->fixed_amount }}
                                        @else
                                            Customer Entered
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm">
                                        @if ($form->is_active)
                                            <span class="text-green-600">Active</span>
                                        @else
                                            <span class="text-red-600">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2 text-sm">{{ $form->paid_count }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $form->pending_count }}</td>
                                    <td class="px-4 py-2 text-sm">₹{{ number_format($form->paid_total ?? 0, 2) }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        <input type="text" readonly id="link-{{ $form->id }}"
                                            value="{{ route('form.public', $form->uuid) }}"
                                            class="hidden">
                                        <button type="button"
                                            onclick="navigator.clipboard.writeText(document.getElementById('link-{{ $form->id }}').value); this.innerText='Copied';"
                                            class="text-blue-600 underline">Copy Link</button>
                                        <a href="{{ route('submissions.index', $form) }}" class="text-indigo-600 underline ml-3">Submissions</a>                                            
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-4 text-center text-gray-500">No forms assigned yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Latest Submissions</h3>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Form</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($recent as $submission)
                                <tr>
                                    <td class="px-4 py-2 text-sm">{{ $submission->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $submission->form->title }}</td>
                                    <td class="px-4 py-2 text-sm">₹{{ $submission->amount }}</td>
                                    <td class="px-4 py-2 text-sm">
                                        @if ($submission->payment_status === 'paid')
                                            <span class="text-green-600">Paid</span>
                                        @elseif ($submission->payment_status === 'failed')
                                            <span class="text-red-600">Failed</span>
                                        @else
                                            <span class="text-yellow-600">Pending</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-4 text-center text-gray-500">No submissions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>