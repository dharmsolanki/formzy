<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transaction Report') }}
        </h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">

        {{-- 1. Exact ID search --}}
        <x-card title="Find by ID" class="mb-6">
            <form method="GET" action="{{ route('reports.transactions') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end">
                <div class="flex-1">
                    <x-input-label for="ref" value="Payment ID, Order ID or Reference" />
                    <input type="text" name="ref" id="ref" value="{{ $filters['ref'] ?? '' }}"
                           placeholder="pay_xxxxxxxxxxxxxx / order_xxxxxxxxxxxxxx / reference"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm font-mono focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('ref')" class="mt-1" />
                    <p class="text-xs text-gray-500 mt-1">Exact match only.</p>
                </div>
                <div class="flex items-center gap-3">
                    <x-primary-button>{{ __('Search') }}</x-primary-button>
                    <a href="{{ route('reports.transactions') }}" class="text-sm text-gray-600 hover:text-gray-900">Reset</a>
                </div>
            </form>
        </x-card>

        {{-- 2. Find without ID --}}
        <x-card title="Find without ID" class="mb-6">
            <form method="GET" action="{{ route('reports.transactions') }}"
                  x-data="txFilters({
                      url: @js(route('admin.merchants.forms', ['merchant' => '__ID__'])),
                      merchant: @js((string) ($filters['merchant_id'] ?? '')),
                      form: @js((string) ($filters['form_id'] ?? ''))
                  })"
                  class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 items-end">

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

                <div class="sm:col-span-2">
                    <x-input-label for="q" value="Customer data (email, phone or name)" />
                    <input type="text" name="q" id="q" value="{{ $filters['q'] ?? '' }}" placeholder="At least 3 characters"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('q')" class="mt-1" />
                    <p class="text-xs text-gray-500 mt-1">Needs a merchant, form or date range along with it.</p>
                </div>

                <div>
                    <x-input-label for="amount" value="Amount (exact)" />
                    <input type="number" step="0.01" min="0" name="amount" id="amount" value="{{ $filters['amount'] ?? '' }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('amount')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select name="status" id="status"
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All</option>
                        @foreach (['paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed'] as $value => $label)
                            <option value="{{ $value }}" {{ ($filters['status'] ?? '') === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="from" value="From" />
                    <input type="date" name="from" id="from" value="{{ $filters['from'] ?? '' }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('from')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="to" value="To" />
                    <input type="date" name="to" id="to" value="{{ $filters['to'] ?? '' }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <x-input-error :messages="$errors->get('to')" class="mt-1" />
                </div>

                <div class="flex items-center gap-3 sm:col-span-2 lg:col-span-4">
                    <x-primary-button>{{ __('Search') }}</x-primary-button>
                    <a href="{{ route('reports.transactions') }}" class="text-sm text-gray-600 hover:text-gray-900">Reset</a>
                </div>
            </form>
        </x-card>

        {{-- Results --}}
        <x-card :title="$hasFilters ? 'Results' : 'Latest Transactions'">
            <div class="overflow-x-auto -mx-6 -my-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Merchant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Form</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Reference</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer (masked)</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($submissions as $s)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $s->created_at->format('d M Y, h:i A') }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $s->form->user->company_name }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $s->form->title }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">₹{{ $s->amount }}</td>
                                <td class="px-6 py-3 text-sm"><x-badge :status="$s->payment_status" /></td>
                                <td class="px-6 py-3 text-xs font-mono text-gray-600 whitespace-nowrap">{{ $s->razorpay_order_id }}</td>
                                <td class="px-6 py-3 text-xs font-mono text-gray-600 whitespace-nowrap">{{ $s->razorpay_payment_id ?: '-' }}</td>
                                <td class="px-6 py-3 text-xs font-mono text-gray-600 whitespace-nowrap">{{ $s->uuid }}</td>
                                <td class="px-6 py-3 text-sm text-gray-700">{{ $s->customer_preview }}</td>
                                <td class="px-6 py-3 text-sm whitespace-nowrap">
                                    <a href="{{ route('reports.transactions.show', $s->uuid) }}" class="text-indigo-600 hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-6 text-center text-gray-500">
                                    {{ $hasFilters ? 'No transactions match this search.' : 'No transactions yet.' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4 text-sm text-gray-500">{{ $submissions->total() }} transaction(s)</div>
        <div class="mt-2">{{ $submissions->links() }}</div>
    </div>

    <script>
        function txFilters(cfg) {
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