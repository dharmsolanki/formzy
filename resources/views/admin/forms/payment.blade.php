<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Configuration') }} - {{ $form->title }}
        </h2>
    </x-slot>

    @php
        $config = $form->paymentConfig;
        $hasConfig = $config && $config->is_verified;
        $savedKeyId = $hasConfig ? $config->razorpay_key_id : null;
        $mode = $savedKeyId
            ? (str_starts_with($savedKeyId, 'rzp_live_') ? 'Live' : 'Test')
            : null;
        $webhookUrl = route('webhooks.razorpay', $form->uuid);
        $isLocalUrl = str_contains($webhookUrl, '127.0.0.1') || str_contains($webhookUrl, 'localhost');
    @endphp

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl">

            <x-wizard-steps :current="2" />

            @if (session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if ($form->is_complete && $form->is_active)
                <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg text-sm">
                    ⚠ This form is live. Changing keys can break payments in progress. Set the form to Inactive first, then edit.
                </div>
            @endif

            @if ($hasConfig)
                <x-card title="Saved Credentials" class="mb-6">
                    <dl class="text-sm divide-y divide-gray-100">
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500">Key ID</dt>
                            <dd class="font-mono text-gray-900">{{ $savedKeyId }}</dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500">Mode</dt>
                            <dd>
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-medium {{ $mode === 'Live' ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }}">{{ $mode }}</span>
                            </dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500">Key Secret</dt>
                            <dd class="text-gray-900">●●●●●●●●●●●● <span class="text-gray-400">(hidden for security)</span></dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500">Webhook Secret</dt>
                            <dd>
                                @if ($config->webhook_secret)
                                    <span class="text-green-600">●●●●●●●● saved</span>
                                @else
                                    <span class="text-yellow-600">Not set</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-gray-500">Verified</dt>
                            <dd><x-badge status="active">Verified</x-badge></dd>
                        </div>
                    </dl>
                    <p class="text-xs text-gray-500 mt-3">To keep these credentials, leave the fields below empty and click Next. To replace, enter the new Key ID and Key Secret.</p>
                </x-card>
            @endif

            <form method="POST" action="{{ route('admin.forms.payment.store', $form) }}">
                @csrf

                {{-- Razorpay keys --}}
                <x-card title="Razorpay Keys" class="mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="razorpay_key_id" value="Razorpay Key ID" />
                            <x-text-input id="razorpay_key_id" name="razorpay_key_id" type="text" class="mt-1 block w-full"
                                :value="old('razorpay_key_id', $savedKeyId)" placeholder="rzp_test_xxxxxxxxxxxx"
                                :required="! $hasConfig" />
                            <x-input-error :messages="$errors->get('razorpay_key_id')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="razorpay_key_secret" value="Razorpay Key Secret" />
                            <x-text-input id="razorpay_key_secret" name="razorpay_key_secret" type="password" class="mt-1 block w-full"
                                autocomplete="new-password" :required="! $hasConfig" />
                            <x-input-error :messages="$errors->get('razorpay_key_secret')" class="mt-1" />
                            <p class="text-xs text-gray-500 mt-1">
                                @if ($hasConfig)
                                    Leave empty to keep the saved secret. Required only if you change the Key ID.
                                @else
                                    Never pre-filled for security.
                                @endif
                            </p>
                        </div>
                    </div>
                </x-card>

                {{-- Webhook --}}
                <x-card title="Webhook (recommended)" class="mb-6">
                    <div class="mb-4">
                        <x-input-label value="Webhook URL" />
                        <div class="flex gap-2 mt-1">
                            <input type="text" id="webhook_url" readonly value="{{ $webhookUrl }}"
                                class="block w-full border-gray-300 rounded-md shadow-sm bg-gray-50 text-sm font-mono">
                            <button type="button"
                                onclick="navigator.clipboard.writeText(document.getElementById('webhook_url').value); this.innerText='Copied';"
                                class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-md text-sm whitespace-nowrap">Copy</button>
                        </div>
                        @if ($isLocalUrl)
                            <p class="text-xs text-yellow-700 mt-1">This is a local URL. Razorpay cannot reach it. It will work after deployment (or with ngrok).</p>
                        @endif
                    </div>

                    <div class="mb-4">
                        <x-input-label for="webhook_secret" value="Webhook Secret" />
                        <x-text-input id="webhook_secret" name="webhook_secret" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                        <x-input-error :messages="$errors->get('webhook_secret')" class="mt-1" />
                        <p class="text-xs text-gray-500 mt-1">Same value as set in Razorpay while creating the webhook. Leave empty to keep the saved one.</p>
                    </div>

                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm text-gray-700">
                        <div class="font-medium text-gray-900 mb-2">In the merchant's Razorpay Dashboard:</div>
                        <ol class="list-decimal list-inside space-y-1">
                            <li>Go to Settings → Webhooks → Add New Webhook</li>
                            <li>Paste the Webhook URL shown above</li>
                            <li>Secret: enter a strong random value (min 8 characters) and enter the same value here</li>
                            <li>Select events: <strong>payment.captured</strong> and <strong>payment.failed</strong></li>
                            <li>Click Create Webhook</li>
                        </ol>
                        <p class="text-xs text-gray-500 mt-2">The webhook is optional. Payments work without it, but if the customer closes the browser early, the status may stay pending.</p>
                    </div>
                </x-card>

                <div class="flex justify-between items-center">
                    <a href="{{ route('admin.forms.edit', $form) }}" class="text-sm text-gray-600 hover:text-gray-900">&larr; Back to Step 1</a>
                    <x-primary-button>{{ $hasConfig ? __('Next: Receipt') : __('Verify & Continue') }}</x-primary-button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>