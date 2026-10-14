<x-public-layout title="Form Unavailable">

    @isset($merchant)
        <x-merchant-brand :merchant="$merchant" />
    @endisset

    <x-card>
        <div class="text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-gray-100 text-gray-500 flex items-center justify-center text-3xl">!</div>
            <h1 class="mt-4 text-xl font-semibold text-gray-900">Not Available</h1>
            <p class="text-sm text-gray-600 mt-2">{{ $message }}</p>
        </div>
    </x-card>

</x-public-layout>