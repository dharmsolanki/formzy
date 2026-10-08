<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Admin Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Merchants</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['merchants'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Live Forms</div>
                    <div class="text-2xl font-semibold text-green-600">{{ $stats['forms_live'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Draft Forms</div>
                    <div class="text-2xl font-semibold text-yellow-600">{{ $stats['forms_draft'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Paid Payments</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['paid_count'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Pending Payments</div>
                    <div class="text-2xl font-semibold text-gray-900">{{ $stats['pending_count'] }}</div>
                </div>
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-sm text-gray-500">Total Collected</div>
                    <div class="text-2xl font-semibold text-gray-900">₹{{ number_format($stats['paid_total'], 2) }}</div>
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
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Merchant</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($recent as $submission)
                                <tr>
                                    <td class="px-4 py-2 text-sm">{{ $submission->created_at->format('d M Y, h:i A') }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $submission->form->title }}</td>
                                    <td class="px-4 py-2 text-sm">{{ $submission->form->user->company_name }}</td>
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
                                    <td colspan="5" class="px-4 py-4 text-center text-gray-500">No submissions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>