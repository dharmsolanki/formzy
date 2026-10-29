<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Customer Payment Report') }}
        </h2>
    </x-slot>

    @php
        $formsForJs = $allForms->map(function ($f) {
            return [
                'id' => $f->id,
                'title' => $f->title,
                'user_id' => $f->user_id,
            ];
        })->values()->all();
    @endphp

    <div class="py-8 px-4 sm:px-6 lg:px-8">

        {{-- Filters --}}
        <x-card class="mb-6">
            <form method="GET" action="{{ route('reports.index') }}"
                  x-data="reportFilters({
                      isAdmin: @js($isAdmin),
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
            <x-card title="Form-wise Summary">
                <div class="overflow-x-auto -mx-6 -my-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Form</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registrations</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Paid</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pending</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Collected</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customers</th>
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
                                    <td class="px-6 py-3 text-sm text-gray-900">{{ $row->registrations }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-900">{{ (int) $row->paid_count }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-900">{{ (int) $row->pending_count }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">₹{{ number_format($row->total, 2) }}</td>
                                    <td class="px-6 py-3 text-sm whitespace-nowrap">
                                        <a href="{{ route('reports.index', array_filter(['merchant_id' => $isAdmin ? $row->user_id : null, 'form_id' => $row->form_id])) }}"
                                           class="text-indigo-600 hover:underline">View customers</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-6 py-6 text-center text-gray-500">No registrations.</td></tr>
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

        {{-- Customers: only when one form is chosen --}}
        @if ($selectedForm)
            <x-card :title="'Customers - ' . $selectedForm->title">
                <x-slot name="action">
                    <a href="{{ route('reports.export', request()->query()) }}"
                       class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium whitespace-nowrap">
                        Export Excel
                    </a>
                </x-slot>

                <div class="overflow-x-auto -mx-6 -my-6">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">Ref No.</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                @foreach ($selectedForm->fields as $field)
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase whitespace-nowrap">{{ $field->label }}</th>
                                @endforeach
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse ($submissions as $s)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-3 text-sm text-gray-600">{{ $s->id }}</td>
                                    <td class="px-6 py-3 text-sm text-gray-600 whitespace-nowrap">{{ $s->created_at->format('d M Y, h:i A') }}</td>
                                    @foreach ($selectedForm->fields as $field)
                                        <td class="px-6 py-3 text-sm text-gray-900">{{ $s->answer($field) }}</td>
                                    @endforeach
                                    <td class="px-6 py-3 text-sm text-gray-900 whitespace-nowrap">₹{{ $s->amount }}</td>
                                    <td class="px-6 py-3 text-sm"><x-badge :status="$s->payment_status" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $selectedForm->fields->count() + 4 }}" class="px-6 py-6 text-center text-gray-500">No customers found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-card>

            <div class="mt-4 text-sm text-gray-500">{{ $submissions->total() }} registration(s)</div>
            <div class="mt-1 text-xs text-gray-500">
                The Excel file has the same filters. Sheet 1 has the current fields of this form.
                If any field was removed from the form, its old answers are in a second sheet named "Removed fields".
            </div>
            <div class="mt-2">{{ $submissions->links() }}</div>
        @else
            <x-card>
                <p class="text-sm text-gray-600">
                    Select {{ $isAdmin ? 'a merchant and a form' : 'a form' }} above, or use "View customers" in the Form-wise Summary, to see what each customer filled in.
                </p>
            </x-card>
        @endif
    </div>

    <script>
        function reportFilters(cfg) {
            return {
                forms: cfg.forms,
                merchant: cfg.merchant,
                formTs: null,

                options() {
                    // Admin must pick a merchant first
                    if (cfg.isAdmin && !this.merchant) {
                        return [];
                    }

                    const list = this.forms
                        .filter(f => !cfg.isAdmin || String(f.user_id) === String(this.merchant))
                        .map(f => ({ id: String(f.id), label: f.title }));

                    return [{ id: '', label: 'All forms' }, ...list];
                },

                refresh(selected) {
                    const opts = this.options();

                    this.formTs.clear(true);
                    this.formTs.clearOptions();
                    this.formTs.addOptions(opts);

                    if (opts.length) {
                        this.formTs.enable();
                        this.formTs.setValue(selected ?? '', true);
                    } else {
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
                        placeholder: cfg.isAdmin ? 'Select merchant first' : 'All forms',
                    });

                    this.refresh(String(cfg.form || ''));

                    if (this.$refs.merchant) {
                        this.$refs.merchant.addEventListener('change', (e) => {
                            this.merchant = e.target.value;
                            this.refresh('');
                        });
                    }
                },
            };
        }
    </script>
</x-app-layout>