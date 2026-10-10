@props(['status' => 'default'])

@php
    $styles = [
        'paid'     => 'bg-green-100 text-green-700',
        'active'   => 'bg-green-100 text-green-700',
        'live'     => 'bg-green-100 text-green-700',
        'pending'  => 'bg-yellow-100 text-yellow-700',
        'draft'    => 'bg-yellow-100 text-yellow-700',
        'failed'   => 'bg-red-100 text-red-700',
        'inactive' => 'bg-red-100 text-red-700',
        'default'  => 'bg-gray-100 text-gray-700',
    ];

    $class = $styles[strtolower($status)] ?? $styles['default'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium $class"]) }}>
    {{ $slot->isEmpty() ? ucfirst($status) : $slot }}
</span>