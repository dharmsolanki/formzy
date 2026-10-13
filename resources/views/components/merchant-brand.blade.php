@props(['merchant'])

<div class="text-center mb-6">
    @if ($merchant->logoUrl())
        <img src="{{ $merchant->logoUrl() }}" alt="{{ $merchant->company_name }}"
             class="mx-auto h-16 w-auto max-w-[200px] object-contain">
    @endif
    <div class="{{ $merchant->logoUrl() ? 'mt-2 text-lg' : 'text-2xl' }} font-bold text-gray-900 tracking-tight">
        {{ $merchant->company_name }}
    </div>
</div>