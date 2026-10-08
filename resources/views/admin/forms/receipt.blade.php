<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Step 3 of 3: Receipt Message') }} - {{ $form->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <x-wizard-steps :current="3" />
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

                <form method="POST" action="{{ route('admin.forms.receipt.store', $form) }}">
                    @csrf

                    <div class="mb-4">
                        <x-input-label for="success_message" value="Success / Thank-you Message (shown after payment)" />
                        <textarea name="success_message" id="success_message" rows="5" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" placeholder="Thank you for registering! Webinar details will be shared on WhatsApp.">{{ old('success_message', $form->success_message) }}</textarea>
                        <p class="text-xs text-gray-500 mt-1">Optional. Leave blank to show the default message.</p>
                    </div>

                    <div class="flex justify-between items-center">
                        <a href="{{ route('admin.forms.payment', $form) }}" class="text-sm text-gray-600 underline">&larr; Back to Step 2</a>
                        <x-primary-button>{{ __('Finish & Make Live') }}</x-primary-button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>