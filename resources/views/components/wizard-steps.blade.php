@props(['current' => 1])

@php
    $steps = [
        1 => 'Form & Fields',
        2 => 'Configuration',
        3 => 'Receipt',
    ];
@endphp

<div class="mb-6 flex items-center justify-center">
    @foreach ($steps as $number => $label)
        <div class="flex items-center">
            <div class="flex items-center">
                <span class="w-8 h-8 flex items-center justify-center rounded-full text-sm font-semibold
                    {{ $number < $current ? 'bg-green-600 text-white' : ($number === (int) $current ? 'bg-gray-800 text-white' : 'bg-gray-200 text-gray-500') }}">
                    {{ $number < $current ? '✓' : $number }}
                </span>
                <span class="ml-2 text-sm {{ $number === (int) $current ? 'font-semibold text-gray-900' : 'text-gray-500' }}">
                    {{ $label }}
                </span>
            </div>

            @if ($number < count($steps))
                <div class="w-10 sm:w-16 h-0.5 mx-3 {{ $number < $current ? 'bg-green-600' : 'bg-gray-200' }}"></div>
            @endif
        </div>
    @endforeach
</div>