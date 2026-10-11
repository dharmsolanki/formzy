<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Forms') }}
            </h2>
            <a href="{{ route('admin.forms.create') }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                + Create Form
            </a>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-card>
            <div class="overflow-x-auto -mx-6 -my-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Merchant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Link</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($forms as $form)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $form->title }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ $form->user->company_name }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">
                                    @if ($form->amount_type === 'fixed')
                                        ₹{{ $form->fixed_amount }}
                                    @else
                                        Customer Entered
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    @if ($form->is_complete)
                                        <form method="POST" action="{{ route('admin.forms.toggle-status', $form) }}" title="Click to toggle">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit">
                                                <x-badge :status="$form->is_active ? 'active' : 'inactive'" class="cursor-pointer" />
                                            </button>
                                        </form>
                                    @else
                                        <x-badge status="draft">Draft (Step {{ $form->wizard_step }} of 3)</x-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    @if ($form->is_complete)
                                        <a href="{{ route('form.public', $form->uuid) }}" target="_blank"
                                           class="text-indigo-600 hover:underline">View Link</a>
                                    @else
                                        <span class="text-gray-400">Not live</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('admin.forms.edit', $form) }}"
                                           class="text-indigo-600 hover:underline">{{ $form->is_complete ? 'Edit' : 'Continue Setup' }}</a>
                                        <a href="{{ route('submissions.index', $form) }}"
                                           class="text-indigo-600 hover:underline">Submissions</a>
                                        <form method="POST" action="{{ route('admin.forms.destroy', $form) }}"
                                              onsubmit="return confirm('Delete this form? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-6 text-center text-gray-500">No forms yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4">
            {{ $forms->links() }}
        </div>
    </div>
</x-app-layout>