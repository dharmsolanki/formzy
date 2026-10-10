@props(['label', 'value', 'color' => 'text-gray-900'])

<div class="bg-white border border-gray-200 shadow-sm rounded-xl p-5">
    <div class="text-sm text-gray-500">{{ $label }}</div>
    <div class="mt-1 text-2xl font-semibold {{ $color }}">{{ $value }}</div>
</div>