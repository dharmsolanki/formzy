<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Forms') }}
        </h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">

        <x-card class="mb-6">
            <form method="GET" action="{{ route('merchant.forms.index') }}"
                  class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">
                <div>
                    <x-input-label for="form_id" value="Form" />
                    <select name="form_id" id="form_id" class="searchable mt-1 block w-full">
                        <option value="">All forms</option>
                        @foreach ($myForms as $f)
                            <option value="{{ $f->id }}" {{ (string) ($filters['form_id'] ?? '') === (string) $f->id ? 'selected' : '' }}>{{ $f->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="q" value="Title" />
                    <input type="text" name="q" id="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search in title"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('q')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select name="status" id="status"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <x-primary-button>{{ __('Apply') }}</x-primary-button>
                    <a href="{{ route('merchant.forms.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Reset</a>
                </div>
            </form>
        </x-card>

        <x-card>
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
                                        <a href="{{ route('reports.index', ['form_id' => $form->id]) }}"
                                           class="text-indigo-600 hover:underline">Report</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-6 text-center text-gray-500">
                                    {{ ! empty($filters['form_id']) || ! empty($filters['q']) || ! empty($filters['status']) ? 'No forms match these filters.' : 'No forms assigned yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4 text-sm text-gray-500">{{ $forms->total() }} form(s)</div>

        <div class="mt-2">
            {{ $forms->links() }}
        </div>
    </div>
</x-app-layout>