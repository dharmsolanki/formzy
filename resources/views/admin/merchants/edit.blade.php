<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Merchant') }} - {{ $merchant->company_name }}
        </h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl">
            <x-card>
                <form method="POST" action="{{ route('admin.merchants.update', $merchant) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="name" value="Contact Person Name" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $merchant->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="company_name" value="Company / Business Name" />
                            <x-text-input id="company_name" name="company_name" type="text" class="mt-1 block w-full" :value="old('company_name', $merchant->company_name)" required />
                            <x-input-error :messages="$errors->get('company_name')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="email" value="Email" />
                            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $merchant->email)" required />
                            <x-input-error :messages="$errors->get('email')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="phone" value="Phone" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $merchant->phone)" required />
                            <x-input-error :messages="$errors->get('phone')" class="mt-1" />
                        </div>
                    </div>

                    <div class="mt-6 border-t border-gray-100 pt-6">
                        <x-input-label for="logo" value="Logo (PNG, JPG or WebP, max 1 MB)" />

                        @if ($merchant->logoUrl())
                            <div class="mt-2 flex items-center gap-4">
                                <img src="{{ $merchant->logoUrl() }}" alt="Current logo"
                                     class="h-16 w-auto max-w-[160px] object-contain border border-gray-200 rounded-md p-1 bg-white">
                                <label class="flex items-center text-sm text-gray-700">
                                    <input type="checkbox" name="remove_logo" value="1"
                                           class="mr-2 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Remove current logo
                                </label>
                            </div>
                        @endif

                        <input id="logo" name="logo" type="file" accept=".png,.jpg,.jpeg,.webp"
                               class="mt-3 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200">
                        <x-input-error :messages="$errors->get('logo')" class="mt-1" />
                        <p class="text-xs text-gray-500 mt-1">Uploading a new logo replaces the current one.</p>
                    </div>

                    <div class="mt-6 border-t border-gray-100 pt-6">
                        <label class="flex items-center text-sm text-gray-700">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $merchant->is_active) ? 'checked' : '' }}
                                   class="mr-2 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Merchant is active
                        </label>
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('admin.merchants.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                        <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>