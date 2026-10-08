<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $form->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100 min-h-screen py-12">
    <div class="max-w-xl mx-auto px-4">
        <div class="bg-white shadow rounded-lg p-6">

            <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $form->title }}</h1>

            @if ($form->description)
                <p class="text-gray-600 mb-6">{{ $form->description }}</p>
            @endif

            @if ($errors->has('form'))
                <div class="mb-4 bg-red-50 text-red-700 p-4 rounded">
                    {{ $errors->first('form') }}
                </div>
            @endif

            @if ($errors->any() && ! $errors->has('form'))
                <div class="mb-4 bg-red-50 text-red-700 p-3 rounded text-sm">
                    Please correct the highlighted fields below.
                </div>
            @endif

            <form method="POST" action="{{ route('form.submit', $form->uuid) }}">
                @csrf

                @foreach ($form->fields as $field)
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            {{ $field->label }}
                            @if ($field->is_required)
                                <span class="text-red-500">*</span>
                            @endif
                        </label>

                        @if ($field->type === 'text' || $field->type === 'email' || $field->type === 'number')
                            <input
                                type="{{ $field->type }}"
                                name="fields[{{ $field->id }}]"
                                value="{{ old('fields.'.$field->id) }}"
                                {{ $field->is_readonly ? 'readonly' : '' }}
                                {{ $field->is_required ? 'required' : '' }}
                                class="block w-full border-gray-300 rounded-md shadow-sm {{ $field->is_readonly ? 'bg-gray-100' : '' }}"
                            >
                        @elseif ($field->type === 'dropdown')
                            <select name="fields[{{ $field->id }}]" {{ $field->is_required ? 'required' : '' }} class="block w-full border-gray-300 rounded-md shadow-sm">
                                <option value="">-- Select --</option>
                                @foreach ($field->options ?? [] as $option)
                                    <option value="{{ $option }}" {{ old('fields.'.$field->id) === $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        @elseif ($field->type === 'radio')
                            @foreach ($field->options ?? [] as $option)
                                <label class="flex items-center mb-1">
                                    <input type="radio" name="fields[{{ $field->id }}]" value="{{ $option }}" {{ old('fields.'.$field->id) === $option ? 'checked' : '' }} {{ $field->is_required ? 'required' : '' }} class="mr-2">
                                    {{ $option }}
                                </label>
                            @endforeach
                        @elseif ($field->type === 'checkbox')
                            @foreach ($field->options ?? [] as $option)
                                <label class="flex items-center mb-1">
                                    <input type="checkbox" name="fields[{{ $field->id }}][]" value="{{ $option }}" class="mr-2">
                                    {{ $option }}
                                </label>
                            @endforeach
                        @endif

                        @error('fields.' . $field->id)
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endforeach

                @if ($form->amount_type === 'fixed')
                    <div class="mb-4 p-3 bg-gray-50 rounded">
                        <span class="text-sm text-gray-600">Amount to pay:</span>
                        <span class="font-semibold text-lg">₹{{ $form->fixed_amount }}</span>
                    </div>
                @else
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount to pay (₹)</label>
                                                @error('custom_amount')
                            <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <button type="submit" class="w-full bg-gray-800 text-white py-2 rounded-md font-medium">
                    Proceed to Pay
                </button>
            </form>

        </div>
    </div>
</body>
</html>