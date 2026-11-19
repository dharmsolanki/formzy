<x-public-layout :title="$form->title">

    <x-merchant-brand :merchant="$form->user" />

    <x-form-tabs :form="$form" active="register" />

    @php
        // After a validation error the customer's own answers win over the defaults
        $hasOld = session()->hasOldInput();
    @endphp

    <x-card>
        <h1 class="text-2xl font-bold text-gray-900">{{ $form->title }}</h1>

        @if ($form->description)
            <p class="text-gray-600 mt-2">{{ $form->description }}</p>
        @endif

        @if ($form->expires_at)
            <p class="mt-3 inline-block bg-yellow-50 text-yellow-800 text-xs px-3 py-1 rounded-full">
                Registration closes on {{ $form->expires_at->format('d M Y, h:i A') }}
            </p>
        @endif

        <div class="mt-6">

            @if ($errors->has('form'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    {{ $errors->first('form') }}
                </div>
            @elseif ($errors->any())
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    Please correct the highlighted fields below.
                </div>
            @endif

            <form method="POST" action="{{ route('form.submit', $form->uuid) }}" id="register-form"
                  onsubmit="var b = this.querySelector('button[type=submit]'); b.disabled = true; b.dataset.label = b.innerText; b.innerText = 'Please wait...';">
                @csrf
                <input type="hidden" name="fields_version" value="{{ $form->fields_version }}">

                @foreach ($form->fields as $field)
                    @php
                        $name = 'fields.' . $field->key;
                        $isMobile = $field->is_system && $field->key === 'mobile';
                        $isSystem = (bool) $field->is_system;
                        $locked = ! $isSystem && $field->is_readonly;
                        $default = (string) ($field->default_value ?? '');

                        // Value to show: what the customer typed, otherwise the admin's default
                        $value = $hasOld ? old($name) : ($isSystem ? null : $default);
                    @endphp

                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $field->label }}
                            @if ($field->is_required)
                                <span class="text-red-500">*</span>
                            @endif
                        </label>

                        @if ($isMobile)
                            <input type="tel" inputmode="numeric" autocomplete="tel"
                                name="fields[{{ $field->key }}]"
                                value="{{ old($name) }}"
                                placeholder="10-digit mobile number"
                                required
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

                        @elseif (in_array($field->type, ['text', 'email', 'number']))
                            <input
                                type="{{ $field->type }}"
                                @if ($field->type === 'number') step="any" @endif
                                name="fields[{{ $field->key }}]"
                                value="{{ $locked ? $default : $value }}"
                                @if ($field->placeholder) placeholder="{{ $field->placeholder }}" @endif
                                {{ $locked ? 'readonly' : '' }}
                                {{ $field->is_required && ! $locked ? 'required' : '' }}
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 {{ $locked ? 'bg-gray-100 text-gray-600' : '' }}">

                        @elseif ($field->type === 'dropdown')
                            @php $chosen = $locked ? $default : $value; @endphp
                            <select name="fields[{{ $field->key }}]"
                                {{ $locked ? 'disabled' : '' }}
                                {{ $field->is_required && ! $locked ? 'required' : '' }}
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 {{ $locked ? 'bg-gray-100 text-gray-600' : '' }}">
                                <option value="">-- Select --</option>
                                @foreach ($field->options ?? [] as $option)
                                    <option value="{{ $option }}" {{ (string) $chosen === (string) $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>

                        @elseif ($field->type === 'radio')
                            @php $chosen = $locked ? $default : $value; @endphp
                            @foreach ($field->options ?? [] as $option)
                                <label class="flex items-center mb-1 text-sm {{ $locked ? 'text-gray-500' : 'text-gray-700' }}">
                                    <input type="radio" name="fields[{{ $field->key }}]" value="{{ $option }}"
                                        {{ (string) $chosen === (string) $option ? 'checked' : '' }}
                                        {{ $locked ? 'disabled' : '' }}
                                        {{ $field->is_required && ! $locked ? 'required' : '' }}
                                        class="mr-2 text-indigo-600 focus:ring-indigo-500">
                                    {{ $option }}
                                </label>
                            @endforeach

                        @elseif ($field->type === 'checkbox')
                            @php
                                $defaultList = array_values(array_filter(array_map('trim', explode(',', $default)), fn ($v) => $v !== ''));
                                $oldChecked = $locked ? $defaultList : ($hasOld ? (array) old($name, []) : $defaultList);
                            @endphp
                            @foreach ($field->options ?? [] as $option)
                                <label class="flex items-center mb-1 text-sm {{ $locked ? 'text-gray-500' : 'text-gray-700' }}">
                                    <input type="checkbox" name="fields[{{ $field->key }}][]" value="{{ $option }}"
                                        {{ in_array($option, $oldChecked, true) ? 'checked' : '' }}
                                        {{ $locked ? 'disabled' : '' }}
                                        class="mr-2 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    {{ $option }}
                                </label>
                            @endforeach
                        @endif

                        @if ($field->help_text)
                            <p class="text-xs text-gray-500 mt-1">{{ $field->help_text }}</p>
                        @endif

                        @error($name)
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                <div class="mt-6 pt-4 border-t border-gray-100">
                    @if ($form->amount_type === 'fixed')
                        <div class="flex items-center justify-between mb-4">
                            <span class="text-sm text-gray-600">Amount to pay</span>
                            <span class="text-xl font-semibold text-gray-900">₹{{ $form->fixed_amount }}</span>
                        </div>
                    @else
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount to pay (₹)</label>
                            <input type="number" name="custom_amount" step="0.01" min="1" max="500000"
                                value="{{ old('custom_amount') }}" required
                                class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('custom_amount')
                                <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @endif

                    <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-md font-medium">
                        Proceed to Pay
                    </button>
                </div>
            </form>
        </div>
    </x-card>

    <script>
        // If the customer comes back with the browser Back button, make the button usable again
        window.addEventListener('pageshow', function (e) {
            var b = document.querySelector('#register-form button[type=submit]');
            if (b && e.persisted) {
                b.disabled = false;
                b.innerText = b.dataset.label || 'Proceed to Pay';
            }
        });
    </script>

</x-public-layout>