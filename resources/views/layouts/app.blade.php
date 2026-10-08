<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Formzy') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-gray-100">
        @php
            $isAdmin = auth()->user()->hasRole('admin');

            $menu = $isAdmin
                ? [
                    ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
                    ['label' => 'Merchants', 'route' => 'admin.merchants.index', 'active' => 'admin.merchants.*', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-4.13a4 4 0 110-8 4 4 0 010 8z'],
                    ['label' => 'Forms', 'route' => 'admin.forms.index', 'active' => 'admin.forms.*', 'icon' => 'M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v14l-4-2-3 2-3-2-4 2V6a2 2 0 012-2z'],
                ]
                : [
                    ['label' => 'Dashboard', 'route' => 'merchant.dashboard', 'active' => 'merchant.dashboard', 'icon' => 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
                ];
        @endphp

        <div x-data="{ sidebar: false }" class="min-h-screen">

            {{-- Mobile overlay --}}
            <div x-show="sidebar" x-transition.opacity @click="sidebar = false"
                 class="fixed inset-0 z-30 bg-black/50 lg:hidden" style="display:none"></div>

            {{-- Sidebar --}}
            <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-40 w-64 bg-slate-900 text-slate-300 transform transition-transform duration-200 lg:translate-x-0">
                <div class="h-16 flex items-center px-6 border-b border-slate-800">
                    <a href="{{ $isAdmin ? route('admin.dashboard') : route('merchant.dashboard') }}"
                       class="text-xl font-bold text-white tracking-tight">
                        Form<span class="text-indigo-400">zy</span>
                    </a>
                </div>

                <nav class="px-3 py-4 space-y-1">
                    <div class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">
                        {{ $isAdmin ? 'Admin' : 'Merchant' }}
                    </div>

                    @foreach ($menu as $item)
                        @php $active = request()->routeIs($item['active']); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                                  {{ $active ? 'bg-indigo-600 text-white' : 'hover:bg-slate-800 hover:text-white' }}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                            </svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>
            </aside>

            {{-- Main area --}}
            <div class="lg:pl-64 flex flex-col min-h-screen">

                {{-- Top bar --}}
                <div class="sticky top-0 z-20 h-16 bg-white border-b border-gray-200 flex items-center justify-between px-4 sm:px-6">
                    <button @click="sidebar = true" class="lg:hidden p-2 -ml-2 text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="hidden lg:block"></div>

                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 focus:outline-none">
                                <span class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-semibold">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </span>
                                <span class="hidden sm:inline">{{ Auth::user()->name }}</span>
                                <svg class="fill-current h-4 w-4" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>

                {{-- Page heading --}}
                @isset($header)
                    <header class="bg-white border-b border-gray-200">
                        <div class="px-4 sm:px-6 lg:px-8 py-5">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                {{-- Page content --}}
                <main class="flex-1">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>