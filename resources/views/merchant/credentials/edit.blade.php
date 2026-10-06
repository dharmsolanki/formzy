<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Razorpay Credentials') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if (session('success'))
                    <div class="mb-4 bg-green-50 text-green-700 p-4 rounded">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 text-red-700 p-4 rounded">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($credential && $credential->is_verified)
                    <div class="mb-4 bg-blue-50 text-blue-700 p-4 rounded text-sm">
                        ✓ Credentials already verified and saved. Submit below to update.
                    </div>
                @endif

                <form method="POST" action="{{ route('merchant.credentials.update') }}">
                    @csrf

                    <div class="mb-4">
                        <x-input-label for="razorpay_key_id" value="Razorpay Key ID" />
                        <x-text-input id="razorpay_key_id" name="razorpay_key_id" type="text" class="mt-1 block w-full" :value="old('razorpay_key_id')" placeholder="rzp_test_xxxxxxxxxxxx" required />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="razorpay_key_secret" value="Razorpay Key Secret" />
                        <x-text-input id="razorpay_key_secret" name="razorpay_key_secret" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                        <p class="text-xs text-gray-500 mt-1">For security, this field is never pre-filled. Re-enter to update.</p>
                    </div>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Verify & Save') }}</x-primary-button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>