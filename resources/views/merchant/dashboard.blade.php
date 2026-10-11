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

        <x-card title="My Forms" class="mb-8">
            <div class="overflow-x-auto -mx-6 -my-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pending</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Collected</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($forms as $form)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $form->title }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">
                                    @if ($form->amount_type === 'fixed')
                                        ₹{{ $form->fixed_amount }}
                                    @else
                                        Customer Entered
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    <x-badge :status="$form->is_active ? 'active' : 'inactive'" />
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $form->paid_count }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $form->pending_count }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">₹{{ number_format($form->paid_total ?? 0, 2) }}</td>
                                <td class="px-6 py-3 text-sm whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <input type="text" readonly id="link-{{ $form->id }}"
                                            value="{{ route('form.public', $form->uuid) }}" class="hidden">
                                        <button type="button"
                                            onclick="navigator.clipboard.writeText(document.getElementById('link-{{ $form->id }}').value); this.innerText='Copied';"
                                            class="text-indigo-600 hover:underline">Copy Link</button>
                                        <a href="{{ route('submissions.index', $form) }}"
                                           class="text-indigo-600 hover:underline">Submissions</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-6 text-center text-gray-500">No forms assigned yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <x-card title="Latest Submissions">
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