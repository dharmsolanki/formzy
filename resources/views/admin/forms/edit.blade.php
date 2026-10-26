<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Form') }} - {{ $form->title }}
        </h2>
    </x-slot>

    @php
        $systemDefs = [
            'email'  => ['label' => 'Email', 'type' => 'email'],
            'mobile' => ['label' => 'Mobile Number', 'type' => 'text'],
        ];

        $initialFields = [];

        if (old('fields') !== null) {
            // After a validation error: restore what the admin typed
            foreach (array_values((array) old('fields')) as $f) {
                $key = (string) ($f['key'] ?? '');
                $isSystem = isset($systemDefs[$key]);

                $initialFields[] = [
                    'key' => $key,
                    'is_system' => $isSystem,
                    'label' => $isSystem ? $systemDefs[$key]['label'] : ($f['label'] ?? ''),
                    'type' => $isSystem ? $systemDefs[$key]['type'] : ($f['type'] ?? 'text'),
                    'options' => $f['options'] ?? '',
                    'is_required' => $isSystem ? true : isset($f['is_required']),
                    'is_readonly' => $isSystem ? false : isset($f['is_readonly']),
                ];
            }
        } else {
            foreach ($form->fields as $f) {
                $initialFields[] = [
                    'key' => (string) $f->key,
                    'is_system' => (bool) $f->is_system,
                    'label' => $f->label,
                    'type' => $f->type,
                    'options' => is_array($f->options) ? implode(', ', $f->options) : '',
                    'is_required' => (bool) $f->is_required,
                    'is_readonly' => (bool) $f->is_readonly,
                ];
            }

            // Old forms (made before the fixed fields) get them added at the top on save
            $haveKeys = collect($initialFields)->where('is_system', true)->pluck('key')->all();
            $missing = [];

            foreach ($systemDefs as $key => $def) {
                if (! in_array($key, $haveKeys, true)) {
                    $missing[] = [
                        'key' => $key,
                        'is_system' => true,
                        'label' => $def['label'],
                        'type' => $def['type'],
                        'options' => '',
                        'is_required' => true,
                        'is_readonly' => false,
                    ];
                }
            }

            $initialFields = array_merge($missing, $initialFields);
        }

        // Server errors use the position among custom fields (system rows are not counted)
        $fieldErrors = [];
        foreach ($errors->getMessages() as $k => $msgs) {
            if (preg_match('/^fields\.(\d+)\.(label|type|options)$/', $k, $m)) {
                $fieldErrors[(int) $m[1]][$m[2]] = $msgs[0];
            }
        }

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

            @if (count($fieldErrors))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    Please fix the highlighted fields below.
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
                            <select name="user_id" id="user_id" required class="searchable mt-1 block w-full">
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
                    <p class="text-xs text-gray-500 mb-4">
                        Email and Mobile Number are part of every form and cannot be changed or removed.
                        Add any other fields you need below.
                    </p>

                    <div class="space-y-3">
                        <template x-for="(field, index) in fields" :key="index">
                            <div class="border rounded-lg p-4"
                                 :class="field.is_system ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-gray-50'">

                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-semibold uppercase text-gray-500" x-text="'Field ' + (index + 1)"></span>

                                    <span x-show="field.is_system"
                                          class="text-xs font-medium text-indigo-700 bg-indigo-100 rounded-full px-2 py-0.5">Fixed field</span>

                                    <button type="button" @click="removeField(index)" x-show="!field.is_system"
                                            class="text-red-600 hover:underline text-sm">Remove</button>
                                </div>

                                {{-- System field: only the key is sent --}}
                                <template x-if="field.is_system">
                                    <div>
                                        <input type="hidden" :name="'fields['+index+'][key]'" :value="field.key">
                                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                                            <div>
                                                <div class="text-gray-500 text-xs">Label</div>
                                                <div class="font-medium text-gray-900" x-text="field.label"></div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500 text-xs">Type</div>
                                                <div class="font-medium text-gray-900" x-text="field.key === 'email' ? 'Email' : 'Mobile (10 digits)'"></div>
                                            </div>
                                            <div>
                                                <div class="text-gray-500 text-xs">Required</div>
                                                <div class="font-medium text-gray-900">Yes</div>
                                            </div>
                                        </div>
                                    </div>
                                </template>

                                {{-- Custom field --}}
                                <template x-if="!field.is_system">
                                    <div>
                                        <input type="hidden" :name="'fields['+index+'][key]'" :value="field.key">

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                            <div>
                                                <label class="text-sm text-gray-600">Label</label>
                                                <input type="text" :name="'fields['+index+'][label]'" x-model="field.label" required
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <p class="text-red-600 text-sm mt-1" x-show="errorFor(index, 'label')" x-text="errorFor(index, 'label')"></p>
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
                                                <p class="text-red-600 text-sm mt-1" x-show="errorFor(index, 'type')" x-text="errorFor(index, 'type')"></p>
                                            </div>
                                        </div>

                                        <div class="mb-3" x-show="['dropdown', 'radio', 'checkbox'].includes(field.type)">
                                            <label class="text-sm text-gray-600">Options (comma separated)</label>
                                            <input type="text" :name="'fields['+index+'][options]'" x-model="field.options"
                                                   placeholder="Option A, Option B, Option C"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <p class="text-red-600 text-sm mt-1" x-show="errorFor(index, 'options')" x-text="errorFor(index, 'options')"></p>
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
                errors: @json($fieldErrors),

                // Server errors are numbered among custom fields only
                customIndex(index) {
                    return this.fields.slice(0, index).filter(f => !f.is_system).length;
                },

                errorFor(index, name) {
                    const e = this.errors[this.customIndex(index)];
                    return e ? e[name] : '';
                },

                addField() {
                    this.fields.push({
                        key: '', is_system: false, label: '', type: 'text',
                        options: '', is_required: false, is_readonly: false,
                    });
                },

                removeField(index) {
                    if (!this.fields[index].is_system) {
                        this.fields.splice(index, 1);
                    }
                },
            };
        }
    </script>
</x-app-layout>