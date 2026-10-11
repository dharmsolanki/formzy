<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center gap-4">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Submissions') }} - {{ $form->title }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">{{ $submissions->total() }} total</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ auth()->user()->hasRole('admin') ? route('admin.forms.index') : route('merchant.dashboard') }}"
                   class="text-sm text-gray-600 hover:text-gray-900">&larr; Back</a>
                <a href="{{ route('submissions.export', $form) }}"
                   class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium whitespace-nowrap">
                    Export CSV
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">

        <x-card>
            <div class="overflow-x-auto -mx-6 -my-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Date</th>
                            @foreach ($form->fields as $field)
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">{{ $field->label }}</th>
                            @endforeach
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($submissions as $submission)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $submission->created_at->format('d M Y, h:i A') }}</td>
                                @foreach ($form->fields as $field)
                                    @php
                                        $value = $submission->data[$field->label] ?? '';
                                        $value = is_array($value) ? implode(', ', $value) : $value;
                                    @endphp
                                    <td class="px-6 py-3 text-sm text-gray-900">{{ $value }}</td>
                                @endforeach
                                <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">₹{{ $submission->amount }}</td>
                                <td class="px-6 py-3 text-sm"><x-badge :status="$submission->payment_status" /></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $form->fields->count() + 3 }}" class="px-6 py-6 text-center text-gray-500">No submissions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4">
            {{ $submissions->links() }}
        </div>
    </div>
</x-app-layout>