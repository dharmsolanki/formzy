@props(['form', 'active' => 'register'])

<div class="flex border-b border-gray-200 mb-4">
    <a href="{{ route('form.public', $form->uuid) }}"
       class="px-4 py-2 text-sm font-medium -mb-px border-b-2 {{ $active === 'register' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        Register
    </a>
    <a href="{{ route('form.receipt.tab', $form->uuid) }}"
       class="px-4 py-2 text-sm font-medium -mb-px border-b-2 {{ $active === 'receipt' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
        Receipt
    </a>
</div>