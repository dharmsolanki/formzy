<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Forms') }}
            </h2>
            <a href="{{ route('admin.forms.create') }}" class="bg-gray-800 text-white px-4 py-2 rounded text-sm">
                + Create Form
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if (session('success'))
                    <div class="mb-4 bg-green-50 text-green-700 p-4 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Merchant</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Link</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse ($forms as $form)
                            <tr>
                                <td class="px-4 py-2">{{ $form->title }}</td>
                                <td class="px-4 py-2">{{ $form->user->company_name }}</td>
                                <td class="px-4 py-2">
                                    @if ($form->amount_type === 'fixed')
                                        ₹{{ $form->fixed_amount }}
                                    @else
                                        Customer Entered
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    <form method="POST" action="{{ route('admin.forms.toggle-status', $form) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="{{ $form->is_active ? 'text-green-600' : 'text-red-600' }} underline text-sm">
                                            {{ $form->is_active ? 'Active' : 'Inactive' }}
                                        </button>
                                    </form>
                                </td>
                                <td class="px-4 py-2">
                                    <a href="{{ route('form.public', $form->uuid) }}" target="_blank" class="text-blue-600 text-sm underline">View Link</a>
                                </td>
                                <td class="px-4 py-2 space-x-2">
                                    <a href="{{ route('admin.forms.edit', $form) }}"
                                        class="text-indigo-600 text-sm underline">Edit</a>
                                    <form method="POST" action="{{ route('admin.forms.destroy', $form) }}"
                                        class="inline"
                                        onsubmit="return confirm('Delete this form? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 text-sm underline">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">No forms yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <div class="mt-4">
                    {{ $forms->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
