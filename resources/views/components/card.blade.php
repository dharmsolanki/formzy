@props(['title' => null])

<div {{ $attributes->merge(['class' => 'bg-white border border-gray-200 shadow-sm rounded-xl']) }}>
    @if ($title || isset($action))
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            @if ($title)
                <h3 class="font-semibold text-gray-900">{{ $title }}</h3>
            @endif
            @isset($action)
                <div>{{ $action }}</div>
            @endisset
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>
</div>