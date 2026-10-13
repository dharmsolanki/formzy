<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reset Password') }} - {{ $merchant->company_name }}
        </h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-xl">
            <x-card>
                <p class="text-sm text-gray-600 mb-4">
                    Set a new password for <strong>{{ $merchant->name }}</strong> ({{ $merchant->email }}).
                    The merchant will be logged out from all devices.
                </p>

                <form method="POST" action="{{ route('admin.merchants.password.update', $merchant) }}">
                    @csrf
                    @method('PUT')

                    <div>
                        <x-input-label for="password" value="New Password" />
                        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-1" />
                    </div>

                    <div class="mt-4">
                        <x-input-label for="password_confirmation" value="Confirm New Password" />
                        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                    </div>

                    <div class="flex justify-end gap-3 mt-6">
                        <a href="{{ route('admin.merchants.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                        <x-primary-button>{{ __('Update Password') }}</x-primary-button>
                    </div>
                </form>
            </x-card>
        </div>
    </div>
</x-app-layout>