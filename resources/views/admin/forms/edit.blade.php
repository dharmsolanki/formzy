<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Form') }} - {{ $form->title }}
        </h2>
    </x-slot>

    @php
        // If validation failed, restore what the admin typed. Otherwise load from DB.
        if (old('fields') !== null) {
            $initialFields = collect(old('fields', []))->map(function ($f) {
                return [
                    'label' => $f['label'] ?? '',
                    'type' => $f['type'] ?? 'text',
                    'options' => $f['options'] ?? '',
                    'is_required' => isset($f['is_required']),
                    'is_readonly' => isset($f['is_readonly']),
                ];
            })->values()->all();
        } else {
            $initialFields = $form->fields->map(function ($f) {
                return [
                    'label' => $f->label,
                    'type' => $f->type,
                    'options' => is_array($f->options) ? implode(', ', $f->options) : '',
                    'is_required' => (bool) $f->is_required,
                    'is_readonly' => (bool) $f->is_readonly,
                ];
            })->values()->all();
        }

        if (count($initialFields) === 0) {
            $initialFields = [['label' => '', 'type' => 'text', 'options' => '', 'is_required' => true, 'is_readonly' => false]];
        }

        $hasFieldErrors = collect($errors->keys())->contains(fn ($k) => str_starts_with($k, 'fields'));
        $currentAmountType = old('amount_type', $form->amount_type);
    @endphp

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl">

            <x-wizard-steps :current="1" />

            @if ($form->is_complete && $form->is_active)
                <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg text-sm">
                    ⚠ This form is live. Changing fields can break forms already open in customers' browsers. Set the form to Inactive first, then edit.
                </div>
            @endif

            @if ($hasFieldErrors)
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    Please check the form fields below. Every field needs a label and a type.
                </div>
            @endif

            <form method="POST" action="{{ route('admin.forms.update', $form) }}" x-data="formBuilder()">
                @csrf
                @method('PUT')

                {{-- Basic details --}}
                <x-card title="Basic Details" class="mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="user_id" value="Merchant" />
                            <select name="user_id" id="user_id" required
                                    class="searchable mt-1 block w-full">
                                <option value="">-- Select Merchant --</option>
                                @foreach ($merchants as $merchant)
                                    <option value="{{ $merchant->id }}" {{ old('user_id', $form->user_id) == $merchant->id ? 'selected' : '' }}>
                                        {{ $merchant->company_name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('user_id')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="title" value="Form Title" />
                            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $form->title)" required />
                            <x-input-error :messages="$errors->get('title')" class="mt-1" />
                        </div>

                        <div class="sm:col-span-2">
                            <x-input-label for="description" value="Description" />
                            <textarea name="description" id="description" rows="2"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $form->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-1" />
                        </div>
                    </div>
                </x-card>

                {{-- Amount --}}
                <x-card title="Amount & Expiry" class="mb-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <x-input-label value="Amount Type" />
                            <div class="flex gap-6 mt-2">
                                <label class="flex items-center text-sm text-gray-700">
                                    <input type="radio" name="amount_type" value="fixed" x-model="amountType"
                                           class="mr-2 text-indigo-600 focus:ring-indigo-500">
                                    Fixed Amount
                                </label>
                                <label class="flex items-center text-sm text-gray-700">
                                    <input type="radio" name="amount_type" value="customer_entered" x-model="amountType"
                                           class="mr-2 text-indigo-600 focus:ring-indigo-500">
                                    Customer Entered
                                </label>
                            </div>
                            <x-input-error :messages="$errors->get('amount_type')" class="mt-1" />
                        </div>

                        <div x-show="amountType === 'fixed'">
                            <x-input-label for="fixed_amount" value="Fixed Amount (₹)" />
                            <x-text-input id="fixed_amount" name="fixed_amount" type="number" step="0.01" class="mt-1 block w-full" :value="old('fixed_amount', $form->fixed_amount)" />
                            <x-input-error :messages="$errors->get('fixed_amount')" class="mt-1" />
                        </div>

                        <div>
                            <x-input-label for="expires_at" value="Expires At (optional)" />
                            <input type="datetime-local" name="expires_at" id="expires_at"
                                   value="{{ old('expires_at', $form->expires_at?->format('Y-m-d\TH:i')) }}"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <x-input-error :messages="$errors->get('expires_at')" class="mt-1" />
                        </div>
                    </div>
                </x-card>

                {{-- Fields builder --}}
                <x-card title="Form Fields" class="mb-6">
                    <div class="space-y-3">
                        <template x-for="(field, index) in fields" :key="index">
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-semibold uppercase text-gray-500" x-text="'Field ' + (index + 1)"></span>
                                    <button type="button" @click="removeField(index)"
                                            x-show="fields.length > 1"
                                            class="text-red-600 hover:underline text-sm">Remove</button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                    <div>
                                        <label class="text-sm text-gray-600">Label</label>
                                        <input type="text" :name="'fields['+index+'][label]'" x-model="field.label" required
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    </div>
                                    <div>
                                        <label class="text-sm text-gray-600">Type</label>
                                        <select :name="'fields['+index+'][type]'" x-model="field.type"
                                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="text">Text</option>
                                            <option value="email">Email</option>
                                            <option value="number">Number</option>
                                            <option value="dropdown">Dropdown</option>
                                            <option value="radio">Radio</option>
                                            <option value="checkbox">Checkbox</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3" x-show="['dropdown', 'radio', 'checkbox'].includes(field.type)">
                                    <label class="text-sm text-gray-600">Options (comma separated)</label>
                                    <input type="text" :name="'fields['+index+'][options]'" x-model="field.options"
                                           placeholder="Option A, Option B, Option C"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>

                                <div class="flex gap-6">
                                    <label class="flex items-center text-sm text-gray-700">
                                        <input type="checkbox" :name="'fields['+index+'][is_required]'" value="1" x-model="field.is_required"
                                               class="mr-2 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        Required
                                    </label>
                                    <label class="flex items-center text-sm text-gray-700">
                                        <input type="checkbox" :name="'fields['+index+'][is_readonly]'" value="1" x-model="field.is_readonly"
                                               class="mr-2 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        Read-only
                                    </label>
                                </div>
                            </div>
                        </template>
                    </div>

                    <button type="button" @click="addField()"
                            class="mt-4 inline-flex items-center px-4 py-2 border border-dashed border-gray-300 rounded-md text-sm text-gray-700 hover:bg-gray-50">
                        + Add Field
                    </button>
                </x-card>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('admin.forms.index') }}" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancel</a>
                    <x-primary-button>{{ __('Next: Configuration') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function formBuilder() {
            return {
                amountType: @json($currentAmountType),
                fields: @json($initialFields),
                addField() {
                    this.fields.push({ label: '', type: 'text', options: '', is_required: false, is_readonly: false });
                },
                removeField(index) {
                    if (this.fields.length > 1) {
                        this.fields.splice(index, 1);
                    }
                }
            }
        }
    </script>
</x-app-layout>