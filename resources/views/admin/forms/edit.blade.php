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

        // What is saved in the database, found by key
        $db = [];
        foreach ($form->fields as $f) {
            $db[(string) $f->key] = $f;
        }

        $initialFields = [];

        if (old('fields') !== null) {
            // After a validation error: restore what the admin typed
            foreach (array_values((array) old('fields')) as $f) {
                $key = (string) ($f['key'] ?? '');
                $isSystem = isset($systemDefs[$key]);
                $saved = $db[$key] ?? null;

                $initialFields[] = [
                    'key' => $key,
                    'is_system' => $isSystem,
                    'label' => $isSystem ? $systemDefs[$key]['label'] : ($f['label'] ?? ''),
                    'type' => $isSystem ? $systemDefs[$key]['type'] : ($f['type'] ?? 'text'),
                    'options' => $f['options'] ?? '',
                    'default_value' => $isSystem ? '' : ($f['default_value'] ?? ''),
                    'placeholder' => $isSystem ? '' : ($f['placeholder'] ?? ''),
                    'help_text' => $isSystem ? '' : ($f['help_text'] ?? ''),
                    'is_required' => $isSystem ? true : isset($f['is_required']),
                    'is_readonly' => $isSystem ? false : isset($f['is_readonly']),
                    'original_label' => $saved ? $saved->label : '',
                    'original_type' => $saved ? $saved->type : '',
                    'answered' => $saved ? (int) ($answered[$key] ?? 0) : 0,
                    'label_change' => $f['label_change'] ?? '',
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
                    'default_value' => (string) ($f->default_value ?? ''),
                    'placeholder' => (string) ($f->placeholder ?? ''),
                    'help_text' => (string) ($f->help_text ?? ''),
                    'is_required' => (bool) $f->is_required,
                    'is_readonly' => (bool) $f->is_readonly,
                    'original_label' => $f->label,
                    'original_type' => $f->type,
                    'answered' => (int) ($answered[(string) $f->key] ?? 0),
                    'label_change' => '',
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
                        'default_value' => '',
                        'placeholder' => '',
                        'help_text' => '',
                        'is_required' => true,
                        'is_readonly' => false,
                        'original_label' => $def['label'],
                        'original_type' => $def['type'],
                        'answered' => 0,
                        'label_change' => '',
                    ];
                }
            }

            $initialFields = array_merge($missing, $initialFields);
        }

        // Server errors use the position among custom fields (system rows are not counted)
        $fieldErrors = [];
        foreach ($errors->getMessages() as $k => $msgs) {
            if (preg_match('/^fields\.(\d+)\.(label|type|options|default_value|placeholder|help_text)$/', $k, $m)) {
                $fieldErrors[(int) $m[1]][$m[2]] = $msgs[0];
            }
        }

        $currentAmountType = old('amount_type', $form->amount_type);

        // Same rules as the server: company locked once any record exists, amount once a payment exists
        $companyLocked = $form->submissions()->exists();
        $amountLocked = $form->submissions()->where('payment_status', 'paid')->exists();
    @endphp

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl">

            <x-wizard-steps :current="1" />

            @if ($form->is_complete && $form->is_active)
                <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg text-sm">
                    ⚠ This form is live. Customers who already have the form open will be asked to reload it after you save. Set the form to Inactive first if you are making big changes.
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
                            <x-input-label for="user_id" value="Company" />
                            <select name="user_id" id="user_id" required @disabled($companyLocked) class="searchable mt-1 block w-full">
                                <option value="">-- Select Company --</option>
                                @foreach ($merchants as $merchant)
                                    <option value="{{ $merchant->id }}" {{ old('user_id', $form->user_id) == $merchant->id ? 'selected' : '' }}>
                                        {{ $merchant->company_name }}
                                    </option>
                                @endforeach
                            </select>
                            @if ($companyLocked)
                                <input type="hidden" name="user_id" value="{{ $form->user_id }}">
                                <p class="mt-1 text-xs text-gray-500">Locked: this form already has customer records.</p>
                            @endif
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
                                    <input type="radio" name="amount_type" value="fixed" x-model="amountType" @disabled($amountLocked)
                                           class="mr-2 text-indigo-600 focus:ring-indigo-500">
                                    Fixed Amount
                                </label>
                                <label class="flex items-center text-sm text-gray-700">
                                    <input type="radio" name="amount_type" value="customer_entered" x-model="amountType" @disabled($amountLocked)
                                           class="mr-2 text-indigo-600 focus:ring-indigo-500">
                                    Customer Entered
                                </label>
                            </div>
                            @if ($amountLocked)
                                <input type="hidden" name="amount_type" value="{{ $form->amount_type }}">
                                <input type="hidden" name="fixed_amount" value="{{ $form->fixed_amount }}">
                                <p class="mt-1 text-xs text-gray-500">Locked: this form already has paid records.</p>
                            @endif
                            <x-input-error :messages="$errors->get('amount_type')" class="mt-1" />
                        </div>

                        <div x-show="amountType === 'fixed'">
                            <x-input-label for="fixed_amount" value="Fixed Amount (₹)" />
                            <x-text-input id="fixed_amount" name="fixed_amount" type="number" step="0.01" :disabled="$amountLocked" class="mt-1 block w-full" :value="old('fixed_amount', $form->fixed_amount)" />
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
                        A field that customers have already answered can be renamed only to fix wording.
                        If the question itself changes, choose "New question" and the old field is archived with its data.
                    </p>

                    <div class="space-y-3">
                        <template x-for="(field, index) in fields" :key="index">
                            <div class="border rounded-lg p-4"
                                 :class="[
                                     field.is_system ? 'border-indigo-200 bg-indigo-50' : 'border-gray-200 bg-gray-50',
                                     dragFrom !== null && dragOver === index && dragFrom !== index ? 'ring-2 ring-indigo-400' : '',
                                     dragFrom === index ? 'opacity-50' : ''
                                 ]"
                                 :draggable="dragReady === index ? 'true' : 'false'"
                                 @dragstart="onDragStart($event, index)"
                                 @dragover="if (dragFrom !== null) { $event.preventDefault(); dragOver = index; }"
                                 @drop="onDrop($event, index)"
                                 @dragend="onDragEnd()">

                                <div class="flex items-center justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <span style="cursor: pointer; user-select: none;" class="text-gray-400 hover:text-gray-600 text-lg leading-none"
                                              title="Drag to reorder"
                                              @mousedown="dragReady = index"
                                              @mouseup.window="dragReady = null">⠿</span>
                                        <span class="text-xs font-semibold uppercase text-gray-500" x-text="'Field ' + (index + 1)"></span>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <span x-show="field.is_system"
                                              class="text-xs font-medium text-indigo-700 bg-indigo-100 rounded-full px-2 py-0.5">Fixed field</span>

                                        <span x-show="!field.is_system && field.answered > 0"
                                              class="text-xs font-medium text-gray-700 bg-gray-200 rounded-full px-2 py-0.5"
                                              x-text="field.answered + ' answer(s)'"></span>

                                        <button type="button" @click="toggle(field)" x-show="!field.is_system"
                                                class="text-indigo-600 hover:underline text-sm" x-text="field.open ? 'Close' : 'Edit'"></button>

                                        <button type="button" @click="removeField(index)" x-show="!field.is_system"
                                                class="text-red-600 hover:underline text-sm">Remove</button>
                                    </div>
                                </div>

                                {{-- Short summary while the settings are closed --}}
                                <div x-show="!field.is_system && !field.open" class="text-sm text-gray-700">
                                    <span class="font-medium" x-text="field.label"></span>
                                    <span class="text-gray-500" x-text="'· ' + typeName(field.type) + (field.is_required ? ' · Required' : '') + (field.is_readonly ? ' · Read-only' : '')"></span>
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
                                    <div x-show="field.open">
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

                                        {{-- Asked only when an answered field is renamed or its type changes --}}
                                        <div class="mb-3 bg-yellow-50 border border-yellow-200 rounded-md p-3 text-sm"
                                             x-show="needsChoice(field)">
                                            <div class="font-medium text-yellow-900 mb-2">
                                                <span x-text="field.answered"></span> customer(s) have answered this field. What is this change?
                                            </div>
                                            <label class="flex items-start mb-1 text-gray-800">
                                                <input type="radio" :name="'fields['+index+'][label_change]'" value="wording" x-model="field.label_change"
                                                       class="mt-1 mr-2 text-indigo-600 focus:ring-indigo-500">
                                                <span><strong>Same question</strong>, I am only fixing the wording. Old answers stay with this field.</span>
                                            </label>
                                            <label class="flex items-start text-gray-800">
                                                <input type="radio" :name="'fields['+index+'][label_change]'" value="new" x-model="field.label_change"
                                                       class="mt-1 mr-2 text-indigo-600 focus:ring-indigo-500">
                                                <span><strong>New question.</strong> The old field is archived (hidden from the form, its data is kept in reports) and a new field is created.</span>
                                            </label>
                                        </div>

                                        <div class="mb-3" x-show="['dropdown', 'radio', 'checkbox'].includes(field.type)">
                                            <label class="text-sm text-gray-600">Options (comma separated)</label>
                                            <input type="text" :name="'fields['+index+'][options]'" x-model="field.options"
                                                   placeholder="Option A, Option B, Option C"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <p class="text-red-600 text-sm mt-1" x-show="errorFor(index, 'options')" x-text="errorFor(index, 'options')"></p>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                            <div>
                                                <label class="text-sm text-gray-600">Default value (optional)</label>
                                                <input type="text" :name="'fields['+index+'][default_value]'" x-model="field.default_value"
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <p class="text-xs text-gray-500 mt-1" x-text="defaultHint(field)"></p>
                                                <p class="text-red-600 text-sm mt-1" x-show="errorFor(index, 'default_value')" x-text="errorFor(index, 'default_value')"></p>
                                            </div>

                                            <div x-show="['text', 'email', 'number'].includes(field.type)">
                                                <label class="text-sm text-gray-600">Placeholder (optional)</label>
                                                <input type="text" maxlength="100" :name="'fields['+index+'][placeholder]'" x-model="field.placeholder"
                                                       placeholder="Example text shown inside the box"
                                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                                <p class="text-red-600 text-sm mt-1" x-show="errorFor(index, 'placeholder')" x-text="errorFor(index, 'placeholder')"></p>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="text-sm text-gray-600">Help text (optional)</label>
                                            <input type="text" maxlength="200" :name="'fields['+index+'][help_text]'" x-model="field.help_text"
                                                   placeholder="A short line shown under the field"
                                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <p class="text-red-600 text-sm mt-1" x-show="errorFor(index, 'help_text')" x-text="errorFor(index, 'help_text')"></p>
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
                                                Read-only (needs a default value)
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

                // Settings stay closed so nothing changes by accident; new rows and rows with errors start open
                init() {
                    let custom = 0;
                    this.fields.forEach((f) => {
                        f.open = !f.is_system && (!f.key || !!this.errors[custom]);
                        if (!f.is_system) {
                            custom++;
                        }
                    });
                },

                typeName(type) {
                    return { text: 'Text', email: 'Email', number: 'Number', dropdown: 'Dropdown', radio: 'Radio', checkbox: 'Checkbox' }[type] || type;
                },

                toggle(field) {
                    if (field.open) {
                        if (field.label.trim() === '') {
                            alert('Enter the field label before closing this field.');
                            return;
                        }
                        if (this.needsChoice(field) && !field.label_change) {
                            alert('Answer the question about this change before closing the field.');
                            return;
                        }
                    }
                    field.open = !field.open;
                },

                // Drag and drop order (a drag starts only from the handle)
                dragReady: null,
                dragFrom: null,
                dragOver: null,

                onDragStart(e, index) {
                    if (this.dragReady !== index) {
                        e.preventDefault();
                        return;
                    }
                    this.dragFrom = index;
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/plain', String(index));
                },

                onDrop(e, to) {
                    if (this.dragFrom === null) {
                        return;
                    }
                    e.preventDefault();
                    this.moveField(this.dragFrom, to);
                    this.onDragEnd();
                },

                onDragEnd() {
                    this.dragReady = null;
                    this.dragFrom = null;
                    this.dragOver = null;
                },

                moveField(from, to) {
                    if (from === null || from === to) {
                        return;
                    }
                    const item = this.fields.splice(from, 1)[0];
                    this.fields.splice(to, 0, item);
                },

                // Server errors are numbered among custom fields only
                customIndex(index) {
                    return this.fields.slice(0, index).filter(f => !f.is_system).length;
                },

                errorFor(index, name) {
                    const e = this.errors[this.customIndex(index)];
                    return e ? e[name] : '';
                },

                // Answered field whose label or type was changed
                needsChoice(field) {
                    return field.answered > 0
                        && (field.label.trim() !== field.original_label || field.type !== field.original_type);
                },

                defaultHint(field) {
                    switch (field.type) {
                        case 'email': return 'A valid email address.';
                        case 'number': return 'A number.';
                        case 'dropdown':
                        case 'radio': return 'Must be one of the options above.';
                        case 'checkbox': return 'One or more of the options above, separated by commas.';
                        default: return 'Text that is already filled in for the customer.';
                    }
                },

                addField() {
                    this.fields.push({
                        key: '', is_system: false, label: '', type: 'text',
                        options: '', default_value: '', placeholder: '', help_text: '',
                        is_required: false, is_readonly: false,
                        original_label: '', original_type: '', answered: 0, label_change: '', open: true,
                    });
                },

                removeField(index) {
                    const f = this.fields[index];

                    if (f.is_system) {
                        return;
                    }

                    if (f.answered > 0 && !confirm(
                        f.answered + ' customer(s) have answered "' + f.original_label + '". ' +
                        'The field will be archived: hidden from the form, but its data stays in reports. Continue?'
                    )) {
                        return;
                    }

                    this.fields.splice(index, 1);
                },
            };
        }
    </script>
</x-app-layout>