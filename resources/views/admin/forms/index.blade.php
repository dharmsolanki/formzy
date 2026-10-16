<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Forms') }}
            </h2>
            <a href="{{ route('admin.forms.create') }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium">
                + Create Form
            </a>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">

        @if (session('success'))
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-card class="mb-6">
            <form method="GET" action="{{ route('admin.forms.index') }}"
                  x-data="formFilters({
                      url: @js(route('admin.merchants.forms', ['merchant' => '__ID__'])),
                      merchant: @js((string) ($filters['merchant_id'] ?? '')),
                      form: @js((string) ($filters['form_id'] ?? ''))
                  })"
                  class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 items-end">

                <div>
                    <x-input-label for="merchant_id" value="Merchant" />
                    <select name="merchant_id" id="merchant_id" x-ref="merchant" class="searchable mt-1 block w-full">
                        <option value="">All merchants</option>
                        @foreach ($merchants as $m)
                            <option value="{{ $m->id }}" {{ (string) ($filters['merchant_id'] ?? '') === (string) $m->id ? 'selected' : '' }}>{{ $m->company_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="form_id" value="Form" />
                    <select name="form_id" id="form_id" x-ref="form" class="mt-1 block w-full"></select>
                </div>

                <div>
                    <x-input-label for="q" value="Title" />
                    <input type="text" name="q" id="q" value="{{ $filters['q'] ?? '' }}" placeholder="Search in title"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select name="status" id="status"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All</option>
                        @foreach (['live' => 'Live', 'draft' => 'Draft', 'inactive' => 'Inactive'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center gap-3">
                    <x-primary-button>{{ __('Apply') }}</x-primary-button>
                    <a href="{{ route('admin.forms.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Reset</a>
                </div>
            </form>
        </x-card>

        <x-card>
            <div class="overflow-x-auto -mx-6 -my-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Merchant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Link</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($forms as $form)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $form->title }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ $form->user->company_name }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">
                                    @if ($form->amount_type === 'fixed')
                                        ₹{{ $form->fixed_amount }}
                                    @else
                                        Customer Entered
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    @if ($form->is_complete)
                                        <form method="POST" action="{{ route('admin.forms.toggle-status', $form) }}" title="Click to toggle">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit">
                                                <x-badge :status="$form->is_active ? 'active' : 'inactive'" class="cursor-pointer" />
                                            </button>
                                        </form>
                                    @else
                                        <x-badge status="draft">Draft (Step {{ $form->wizard_step }} of 3)</x-badge>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    @if ($form->is_complete)
                                        <a href="{{ route('form.public', $form->uuid) }}" target="_blank"
                                           class="text-indigo-600 hover:underline">View Link</a>
                                    @else
                                        <span class="text-gray-400">Not live</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm whitespace-nowrap">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ route('admin.forms.edit', $form) }}"
                                           class="text-indigo-600 hover:underline">{{ $form->is_complete ? 'Edit' : 'Continue Setup' }}</a>
                                        <a href="{{ route('reports.index', ['merchant_id' => $form->user_id, 'form_id' => $form->id]) }}"
                                           class="text-indigo-600 hover:underline">Report</a>
                                        <form method="POST" action="{{ route('admin.forms.destroy', $form) }}"
                                              onsubmit="return confirm('Delete this form? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-6 text-center text-gray-500">
                                    {{ ! empty($filters['merchant_id']) || ! empty($filters['q']) || ! empty($filters['status']) ? 'No forms match these filters.' : 'No forms yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4 text-sm text-gray-500">{{ $forms->total() }} form(s)</div>

        <div class="mt-2">
            {{ $forms->links() }}
        </div>
    </div>
    <script>
        function formFilters(cfg) {
            return {
                merchant: cfg.merchant,
                formTs: null,

                async load(selected) {
                    this.formTs.clear(true);
                    this.formTs.clearOptions();

                    if (!this.merchant) {
                        this.formTs.disable();
                        return;
                    }

                    try {
                        const res = await fetch(cfg.url.replace('__ID__', this.merchant), {
                            headers: { 'Accept': 'application/json' },
                        });
                        if (!res.ok) throw new Error('failed');

                        const data = await res.json();
                        this.formTs.addOptions([
                            { id: '', label: 'All forms' },
                            ...data.map(f => ({ id: String(f.id), label: f.title })),
                        ]);
                        this.formTs.enable();
                        this.formTs.setValue(selected ?? '', true);
                    } catch (e) {
                        this.formTs.disable();
                    }
                },

                init() {
                    this.formTs = new TomSelect(this.$refs.form, {
                        allowEmptyOption: true,
                        maxOptions: null,
                        create: false,
                        valueField: 'id',
                        labelField: 'label',
                        searchField: ['label'],
                        placeholder: 'Select merchant first',
                    });

                    this.load(String(cfg.form || ''));

                    this.$refs.merchant.addEventListener('change', (e) => {
                        this.merchant = e.target.value;
                        this.load('');
                    });
                },
            };
        }
    </script>
</x-app-layout>