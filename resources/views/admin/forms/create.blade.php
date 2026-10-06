<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create Form') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                @if ($errors->any())
                    <div class="mb-4 bg-red-50 text-red-700 p-4 rounded">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.forms.store') }}" x-data="formBuilder()">
                    @csrf

                    <div class="mb-4">
                        <x-input-label for="user_id" value="Merchant" />
                        <select name="user_id" id="user_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
                            <option value="">-- Select Merchant --</option>
                            @foreach ($merchants as $merchant)
                                <option value="{{ $merchant->id }}" {{ old('user_id') == $merchant->id ? 'selected' : '' }}>
                                    {{ $merchant->company_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <x-input-label for="title" value="Form Title" />
                        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title')" required />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="description" value="Description" />
                        <textarea name="description" id="description" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-4">
                        <x-input-label value="Amount Type" />
                        <div class="flex gap-4 mt-1">
                            <label class="flex items-center">
                                <input type="radio" name="amount_type" value="fixed" x-model="amountType" checked class="mr-2">
                                Fixed Amount
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="amount_type" value="customer_entered" x-model="amountType" class="mr-2">
                                Customer Entered
                            </label>
                        </div>
                    </div>

                    <div class="mb-4" x-show="amountType === 'fixed'">
                        <x-input-label for="fixed_amount" value="Fixed Amount (₹)" />
                        <x-text-input id="fixed_amount" name="fixed_amount" type="number" step="0.01" class="mt-1 block w-full" :value="old('fixed_amount')" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="expires_at" value="Expires At (optional)" />
                        <input type="datetime-local" name="expires_at" id="expires_at" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" value="{{ old('expires_at') }}">
                    </div>

                    <hr class="my-6">

                    <h3 class="font-semibold text-lg mb-3">Form Fields</h3>

                    <template x-for="(field, index) in fields" :key="index">
                        <div class="border rounded p-4 mb-3 bg-gray-50">
                            <div class="grid grid-cols-2 gap-3 mb-2">
                                <div>
                                    <label class="text-sm text-gray-600">Label</label>
                                    <input type="text" :name="'fields['+index+'][label]'" x-model="field.label" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" required>
                                </div>
                                <div>
                                    <label class="text-sm text-gray-600">Type</label>
                                    <select :name="'fields['+index+'][type]'" x-model="field.type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                                        <option value="text">Text</option>
                                        <option value="email">Email</option>
                                        <option value="number">Number</option>
                                        <option value="dropdown">Dropdown</option>
                                        <option value="radio">Radio</option>
                                        <option value="checkbox">Checkbox</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-2" x-show="['dropdown', 'radio', 'checkbox'].includes(field.type)">
                                <label class="text-sm text-gray-600">Options (comma separated)</label>
                                <input type="text" :name="'fields['+index+'][options]'" x-model="field.options" placeholder="Option A, Option B, Option C" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            </div>

                            <div class="flex gap-4 items-center">
                                <label class="flex items-center text-sm">
                                    <input type="checkbox" :name="'fields['+index+'][is_required]'" value="1" x-model="field.is_required" class="mr-1">
                                    Required
                                </label>
                                <label class="flex items-center text-sm">
                                    <input type="checkbox" :name="'fields['+index+'][is_readonly]'" value="1" x-model="field.is_readonly" class="mr-1">
                                    Read-only
                                </label>
                                <button type="button" @click="removeField(index)" class="text-red-600 text-sm ml-auto">Remove</button>
                            </div>
                        </div>
                    </template>

                    <button type="button" @click="addField()" class="bg-gray-200 text-gray-800 px-4 py-2 rounded text-sm mb-6">
                        + Add Field
                    </button>

                    <div class="flex justify-end">
                        <x-primary-button>{{ __('Create Form') }}</x-primary-button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script>
        function formBuilder() {
            return {
                amountType: 'fixed',
                fields: [
                    { label: '', type: 'text', options: '', is_required: true, is_readonly: false }
                ],
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