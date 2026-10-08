<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Submissions') }} - {{ $form->title }}
            </h2>
            <a href="{{ route('submissions.export', $form) }}" class="bg-gray-800 text-white px-4 py-2 rounded text-sm">
                Export CSV
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                @foreach ($form->fields as $field)
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $field->label }}</th>
                                @endforeach
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($submissions as $submission)
                                <tr>
                                    <td class="px-4 py-2 text-sm whitespace-nowrap">{{ $submission->created_at->format('d M Y, h:i A') }}</td>
                                    @foreach ($form->fields as $field)
                                        @php
                                            $value = $submission->data[$field->label] ?? '';
                                            $value = is_array($value) ? implode(', ', $value) : $value;
                                        @endphp
                                        <td class="px-4 py-2 text-sm">{{ $value }}</td>
                                    @endforeach
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
                                    <td colspan="{{ $form->fields->count() + 3 }}" class="px-4 py-4 text-center text-gray-500">No submissions yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $submissions->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>