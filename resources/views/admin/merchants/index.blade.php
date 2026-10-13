<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Merchants') }}
            </h2>
            <a href="{{ route('admin.merchants.create') }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                + Add Merchant
            </a>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        <x-card>
            <div class="overflow-x-auto -mx-6 -my-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($merchants as $merchant)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">
                                    <div class="flex items-center gap-3">
                                        @if ($merchant->logoUrl())
                                            <img src="{{ $merchant->logoUrl() }}" alt="" class="h-8 w-8 object-contain rounded border border-gray-200 bg-white">
                                        @endif
                                        {{ $merchant->company_name }}
                                    </div>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ $merchant->name }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ $merchant->email }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ $merchant->phone }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <x-badge :status="$merchant->is_active ? 'active' : 'inactive'" />
                                </td>
                                <td class="px-6 py-3 text-sm whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('admin.merchants.edit', $merchant) }}"
                                           class="text-indigo-600 hover:underline">Edit</a>
                                        <a href="{{ route('admin.merchants.password.edit', $merchant) }}"
                                           class="text-indigo-600 hover:underline">Reset Password</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-6 text-center text-gray-500">No merchants yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4">
            {{ $merchants->links() }}
        </div>
    </div>
</x-app-layout>