<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Step 2 of 3: Configuration') }} - {{ $form->title }}
        </h2>
    </x-slot>

    @php
        $config = $form->paymentConfig;
        $hasConfig = $config && $config->is_verified;
        $savedKeyId = $hasConfig ? $config->razorpay_key_id : null;
        $mode = $savedKeyId
            ? (str_starts_with($savedKeyId, 'rzp_live_') ? 'Live' : 'Test')
            : null;
    @endphp

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <x-wizard-steps :current="2" />
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

                @if ($form->is_complete && $form->is_active)
                    <div class="mb-4 bg-yellow-50 text-yellow-800 p-4 rounded text-sm">
                        ⚠ This form is live. Changing keys can break payments in progress. Set the form to Inactive first, then edit.
                    </div>
                @endif

                @if ($hasConfig)
                    <div class="mb-6 border rounded p-4 bg-gray-50 text-sm">
                        <h3 class="font-semibold text-gray-800 mb-2">Currently saved credentials</h3>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-600">Key ID</span>
                            <span class="font-mono text-gray-900">{{ $savedKeyId }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-600">Mode</span>
                            <span class="{{ $mode === 'Live' ? 'text-red-600' : 'text-blue-600' }} font-medium">{{ $mode }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-600">Key Secret</span>
                            <span class="text-gray-900">●●●●●●●●●●●● (saved, hidden for security)</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-600">Webhook Secret</span>
                            <span class="{{ $config->webhook_secret ? 'text-green-600' : 'text-yellow-600' }}">
                                {{ $config->webhook_secret ? '●●●●●●●● (saved)' : 'Not set' }}
                            </span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-600">Verified</span>
                            <span class="text-green-600">✓ Yes</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">To keep these credentials, leave both fields below unchanged/empty and click Next. To replace, enter new Key ID and Key Secret.</p>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.forms.payment.store', $form) }}">
                    @csrf

                    <div class="mb-4">
                        <x-input-label for="razorpay_key_id" value="Razorpay Key ID" />
                        <x-text-input id="razorpay_key_id" name="razorpay_key_id" type="text" class="mt-1 block w-full" :value="old('razorpay_key_id', $savedKeyId)" placeholder="rzp_test_xxxxxxxxxxxx" :required="! $hasConfig" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="razorpay_key_secret" value="Razorpay Key Secret" />
                        <x-text-input id="razorpay_key_secret" name="razorpay_key_secret" type="password" class="mt-1 block w-full" :required="! $hasConfig" autocomplete="new-password" />
                        <p class="text-xs text-gray-500 mt-1">
                            @if ($hasConfig)
                                Leave empty to keep the saved secret. Required only if you change the Key ID.
                            @else
                                Never pre-filled for security.
                            @endif
                        </p>
                    </div>

                    <hr class="my-6">

                    <h3 class="font-semibold text-gray-800 mb-2">Webhook (recommended)</h3>

                    <div class="mb-4">
                        <x-input-label value="Webhook URL (merchant ke Razorpay Dashboard me add karo)" />
                        <div class="flex gap-2 mt-1">
                            <input type="text" id="webhook_url" readonly
                                value="{{ route('webhooks.razorpay', $form->uuid) }}"
                                class="block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 text-sm font-mono">
                            <button type="button"
                                onclick="navigator.clipboard.writeText(document.getElementById('webhook_url').value); this.innerText='Copied';"
                                class="bg-gray-200 text-gray-800 px-3 py-2 rounded text-sm">Copy</button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Razorpay Dashboard → Settings → Webhooks → Add. Events: <strong>payment.captured</strong>, <strong>payment.failed</strong>.</p>
                    </div>

                    <div class="mb-4">
                        <x-input-label for="webhook_secret" value="Webhook Secret" />
                        <x-text-input id="webhook_secret" name="webhook_secret" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                        <p class="text-xs text-gray-500 mt-1">Wahi secret daalo jo Razorpay me webhook banate waqt set kiya. Khali chhodo to purana rahega.</p>
                    </div>

                    <div class="flex justify-between items-center">
                        <a href="{{ route('admin.forms.edit', $form) }}" class="text-sm text-gray-600 underline">&larr; Back to Step 1</a>
                        <x-primary-button>{{ $hasConfig ? __('Next: Receipt') : __('Verify & Continue') }}</x-primary-button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>