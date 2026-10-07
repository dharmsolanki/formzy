<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complete Payment</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md mx-auto px-4 text-center">
        <div class="bg-white shadow rounded-lg p-8">
            <h1 class="text-xl font-semibold text-gray-900 mb-2">{{ $form->title }}</h1>
            <p class="text-gray-600 mb-6">Amount: ₹{{ $submission->amount }}</p>
            <button id="pay-btn" class="w-full bg-gray-800 text-white py-2 rounded-md font-medium">
                Pay Now
            </button>
        </div>
    </div>

    <script>
        document.getElementById('pay-btn').onclick = function() {
            var options = {
                key: @json($razorpayKeyId),
                amount: {{ (int) round($submission->amount * 100) }},
                currency: 'INR',
                name: @json($form->title),
                description: 'Payment for ' + @json($form->title),
                order_id: @json($razorpayOrderId),
                handler: function(response) {
                    // Send payment response to backend for verification
                    var form = document.createElement('form');
                    form.method = 'POST';
                    form.action = @json(route('form.payment.callback', $submission->uuid));

                    var csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = @json(csrf_token());
                    form.appendChild(csrf);

                    var fields = {
                        razorpay_payment_id: response.razorpay_payment_id,
                        razorpay_order_id: response.razorpay_order_id,
                        razorpay_signature: response.razorpay_signature
                    };

                    for (var key in fields) {
                        var input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = fields[key];
                        form.appendChild(input);
                    }

                    document.body.appendChild(form);
                    form.submit();
                },
                modal: {
                    ondismiss: function() {
                        // Customer closed the popup without paying — do nothing, let them retry
                    }
                }
            };

            var rzp = new Razorpay(options);
            rzp.open();
        };
    </script>
</body>

</html>
