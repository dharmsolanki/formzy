<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reports') }}
        </h2>
    </x-slot>

    @php
        $formsForJs = $allForms->map(function ($f) {
            return [
                'id' => $f->id,
                'title' => $f->title,
                'user_id' => $f->user_id,
                'company' => optional($f->user)->company_name,
            ];
        })->values()->all();
    @endphp

    <div class="py-8 px-4 sm:px-6 lg:px-8">

        {{-- Filters --}}
        <x-card class="mb-6">
            <form method="GET" action="{{ route('reports.index') }}"
                  x-data="reportFilters({
                      forms: @js($formsForJs),
                      merchant: @js((string) ($filters['merchant_id'] ?? '')),
                      form: @js((string) ($filters['form_id'] ?? ''))
                  })"
                  class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">

                @if ($isAdmin)
                    <div>
                        <x-input-label for="merchant_id" value="Merchant" />
                        <select name="merchant_id" id="merchant_id" x-ref="merchant" class="searchable mt-1 block w-full">
                            <option value="">All merchants</option>
                            @foreach ($merchants as $m)
                                <option value="{{ $m->id }}" {{ (string) ($filters['merchant_id'] ?? '') === (string) $m->id ? 'selected' : '' }}>{{ $m->company_name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <x-input-label for="form_id" value="Form" />
                    <select name="form_id" id="form_id" x-ref="form" class="mt-1 block w-full"></select>
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
                </div>

                <div>
                    <x-input-label for="to" value="To" />
                    <input type="date" name="to" id="to" value="{{ $filters['to'] ?? '' }}"
                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                <div class="flex items-center gap-3">
                    <x-primary-button>{{ __('Apply') }}</x-primary-button>
                    <a href="{{ route('reports.index') }}" class="text-sm text-gray-600 hover:text-gray-900">Reset</a>
                </div>
            </form>

            @if ($errors->any())
                <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </x-card>

        {{-- Summary --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <x-stat label="Total Collected" :value="'₹' . number_format($summary->paid_total ?? 0, 2)" />
            <x-stat label="Paid" :value="(int) $summary->paid_count" color="text-green-600" />
            <x-stat label="Pending" :value="(int) $summary->pending_count" color="text-yellow-600" />
            <x-stat label="Failed" :value="(int) $summary->failed_count" color="text-red-600" />
        </div>

        {{-- Form-wise and day-wise --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <x-card title="Form-wise Collection (Paid)">
                <div class="overflow-x-auto -mx-6 -my-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Form</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Collected</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($byForm as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm text-gray-900">
                                        {{ $row->title }}
                                        @if ($isAdmin && $row->company)
                                            <div class="text-xs text-gray-500">{{ $row->company }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-900">{{ $row->cnt }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">₹{{ number_format($row->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-6 text-center text-gray-500">No paid payments.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <x-card title="Day-wise Collection (Paid, last 31 days)">
                <div class="overflow-x-auto -mx-6 -my-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Collected</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($byDay as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">{{ \Carbon\Carbon::parse($row->day)->format('d M Y') }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-900">{{ $row->cnt }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">₹{{ number_format($row->total, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-6 text-center text-gray-500">No paid payments.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>
        </div>

        {{-- Detailed list --}}
        <x-card title="Transactions">
            <div class="overflow-x-auto -mx-6 -my-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            @if ($isAdmin)
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Merchant</th>
                            @endif
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Form</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer Data</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($submissions as $s)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $s->created_at->format('d M Y, h:i A') }}</td>
                                @if ($isAdmin)
                                    <td class="px-6 py-3 text-sm text-gray-600">{{ $s->form->user->company_name }}</td>
                                @endif
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $s->form->title }}</td>
                                <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">₹{{ $s->amount }}</td>
                                <td class="px-6 py-3 text-sm"><x-badge :status="$s->payment_status" /></td>
                                <td class="px-6 py-3 text-xs font-mono text-gray-600">{{ $s->razorpay_payment_id }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <a href="{{ route('submissions.index', $s->form_id) }}" class="text-indigo-600 hover:underline">Open list</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $isAdmin ? 7 : 6 }}" class="px-6 py-6 text-center text-gray-500">No transactions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <div class="mt-4">
            {{ $submissions->links() }}
        </div>
    </div>
    <script>
        function reportFilters(cfg) {
            return {
                forms: cfg.forms,
                merchant: cfg.merchant,
                formTs: null,

                options() {
                    const list = this.forms
                        .filter(f => !this.merchant || String(f.user_id) === String(this.merchant))
                        .map(f => ({
                            id: String(f.id),
                            label: (!this.merchant && f.company) ? (f.title + ' — ' + f.company) : f.title,
                        }));

                    return [{ id: '', label: 'All forms' }, ...list];
                },

                init() {
                    this.formTs = new TomSelect(this.$refs.form, {
                        allowEmptyOption: true,
                        maxOptions: null,
                        create: false,
                        valueField: 'id',
                        labelField: 'label',
                        searchField: ['label'],
                        options: this.options(),
                        items: [String(cfg.form || '')],
                    });

                    if (this.$refs.merchant) {
                        this.$refs.merchant.addEventListener('change', (e) => {
                            this.merchant = e.target.value;
                            this.formTs.clear(true);
                            this.formTs.clearOptions();
                            this.formTs.addOptions(this.options());
                            this.formTs.setValue('', true);
                        });
                    }
                },
            };
        }
    </script>
</x-app-layout>