<x-public-layout title="Complete Payment">

    <x-slot name="head">
        <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
    </x-slot>

    <div class="text-center mb-6">
        <div class="text-2xl font-bold text-gray-900 tracking-tight">
            Form<span class="text-indigo-600">zy</span>
        </div>
    </div>

    <x-card>
        <h1 class="text-xl font-semibold text-gray-900">{{ $form->title }}</h1>
        <p class="text-sm text-gray-500 mt-1">Review and complete your payment.</p>

        <div class="mt-6 border border-gray-200 rounded-lg divide-y divide-gray-100 text-sm">
            <div class="flex justify-between px-4 py-3">
                <span class="text-gray-500">Amount</span>
                <span class="font-semibold text-gray-900">₹{{ $submission->amount }}</span>
            </div>
            <div class="flex justify-between px-4 py-3">
                <span class="text-gray-500">Reference</span>
                <span class="font-mono text-xs text-gray-700">{{ $submission->uuid }}</span>
            </div>
        </div>

        <button id="pay-btn" type="button"
            class="mt-6 w-full bg-indigo-600 hover:bg-indigo-700 text-white py-3 rounded-md font-medium">
            Pay ₹{{ $submission->amount }}
        </button>

        <p class="mt-3 text-center text-xs text-gray-400">
            A secure Razorpay window will open. Do not close this page until payment completes.
        </p>
    </x-card>

    <script>
        document.getElementById('pay-btn').onclick = function () {
            var options = {
                key: @json($razorpayKeyId),
                amount: {{ (int) round($submission->amount * 100) }},
                currency: 'INR',
                name: @json($form->title),
                description: 'Payment for ' + @json($form->title),
                order_id: @json($razorpayOrderId),
                handler: function (response) {
                    var f = document.createElement('form');
                    f.method = 'POST';
                    f.action = @json(route('form.payment.callback', $submission->uuid));

                    var fields = {
                        _token: @json(csrf_token()),
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature
                    };

                    for (var key in fields) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = fields[key];
                        f.appendChild(input);
                    }

                    document.body.appendChild(f);
                    f.submit();
                }
            };

            new Razorpay(options).open();
        };
    </script>
</x-public-layout>