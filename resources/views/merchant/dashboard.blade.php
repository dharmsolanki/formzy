<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Merchant Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">

        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <x-stat label="My Forms" :value="$stats['forms']" />
            <x-stat label="Paid Payments" :value="$stats['paid_count']" color="text-green-600" />
            <x-stat label="Pending Payments" :value="$stats['pending_count']" color="text-yellow-600" />
            <x-stat label="Total Collected" :value="'₹' . number_format($stats['paid_total'], 2)" />
        </div>

        <x-card title="Latest Submissions">
            <x-slot name="action">
                <a href="{{ route('reports.index') }}" class="text-sm text-indigo-600 hover:underline">View reports</a>
            </x-slot>

            <div class="overflow-x-auto -mx-6 -my-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Form</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($recent as $submission)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $submission->created_at->format('d M Y, h:i A') }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $submission->form->title }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">₹{{ $submission->amount }}</td>
                                <td class="px-6 py-3 text-sm"><x-badge :status="$submission->payment_status" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-6 text-center text-gray-500">No submissions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

    </div>
</x-app-layout>