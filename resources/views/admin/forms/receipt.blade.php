<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Receipt') }} - {{ $form->title }}
        </h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl">

            <x-wizard-steps :current="3" />

            @if (session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->has('razorpay_key_id'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    {{ $errors->first('razorpay_key_id') }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.forms.receipt.store', $form) }}">
                @csrf

                <x-card title="Success Message" class="mb-6">
                    <x-input-label for="success_message" value="Message shown to the customer after payment" />
                    <textarea name="success_message" id="success_message" rows="5"
                              placeholder="Thank you for registering! Webinar details will be shared on WhatsApp."
                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('success_message', $form->success_message) }}</textarea>
                    <x-input-error :messages="$errors->get('success_message')" class="mt-1" />
                    <p class="text-xs text-gray-500 mt-1">Optional. Maximum 1000 characters. Leave empty to show only the default confirmation.</p>
                </x-card>

                <div class="flex justify-between items-center">
                    <a href="{{ route('admin.forms.payment', $form) }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to Step 2</a>
                    <x-primary-button>{{ __('Finish & Make Live') }}</x-primary-button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>