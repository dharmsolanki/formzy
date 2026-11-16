<x-public-layout :title="'Receipt - ' . $form->title">

    <x-merchant-brand :merchant="$form->user" />

    <x-form-tabs :form="$form" active="receipt" />

    <x-card>
        <h1 class="text-xl font-bold text-gray-900">Find your payment</h1>
        <p class="text-sm text-gray-600 mt-1">
            Enter the email and mobile number you used for <strong>{{ $form->title }}</strong>.
        </p>

        @if ($errors->has('lookup'))
            <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                {{ $errors->first('lookup') }}
            </div>
        @endif

        <form method="POST" action="{{ route('form.receipt.find', $form->uuid) }}" class="mt-4">
            @csrf

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ $email }}" required autocomplete="email"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('email')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                <input type="tel" inputmode="numeric" name="mobile" value="{{ $mobile }}" required autocomplete="tel"
                       placeholder="10-digit mobile number"
                       class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('mobile')
                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-md font-medium">
                Find my payment
            </button>
        </form>
    </x-card>

    @if ($searched)
        <div class="mt-6">
            @if ($results->isEmpty())
                <x-card>
                    <p class="text-sm text-gray-700 text-center">No payment found for these details.</p>
                </x-card>
            @else
                <div class="space-y-3">
                    @foreach ($results as $r)
                        <x-card>
                            <div class="flex items-center justify-between">
                                <div>
                                    <div class="text-lg font-semibold text-gray-900">₹{{ $r->amount }}</div>
                                    <div class="text-xs text-gray-500">{{ $r->created_at->format('d M Y, h:i A') }}</div>
                                </div>
                                <x-badge :status="$r->payment_status" />
                            </div>

                            @if ($r->payment_status === 'paid')
                                <a href="{{ route('form.receipt', $r->uuid) }}" target="_blank"
                                   class="mt-4 block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-md text-sm font-medium">
                                    Download Receipt
                                </a>
                            @elseif ($r->payment_status === 'pending')
                                <p class="mt-3 text-sm text-gray-700">
                                    Payment not confirmed yet. If money was deducted, please contact the organizer and share this reference:
                                </p>
                                <p class="mt-1 font-mono text-xs text-gray-700 break-all">{{ $r->uuid }}</p>
                            @else
                                <p class="mt-3 text-sm text-gray-700">This payment was not completed. You can register again.</p>
                            @endif
                        </x-card>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

</x-public-layout>