@php
    $items = [
        ['route' => 'dashboard', 'label' => __('Dashboard'), 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
        ['route' => 'clients.index', 'label' => __('Clients'), 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z'],
        ['route' => 'cases.index', 'label' => __('Cases'), 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
        ['route' => 'calendar.index', 'label' => __('Calendar'), 'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z'],
    ];
@endphp
<aside class="w-60 bg-white border-e border-gray-200 hidden md:flex md:flex-col">
    <div class="h-16 flex items-center justify-center border-b border-gray-200">
        <a href="{{ route('dashboard') }}" wire:navigate class="text-lexa-700 font-bold text-2xl tracking-tight">LEXA</a>
    </div>
    <nav class="flex-1 p-4 space-y-1">
        @foreach ($items as $item)
            @php
                $hasRoute = \Illuminate\Support\Facades\Route::has($item['route']);
                $isActive = $hasRoute && request()->routeIs($item['route']);
                $url = $hasRoute ? route($item['route']) : '#';
            @endphp
            <a href="{{ $url }}"
               @if ($hasRoute) wire:navigate @endif
               class="flex items-center gap-3 px-3 py-2 rounded-md text-sm font-medium transition
                      {{ $isActive
                          ? 'bg-lexa-50 text-lexa-700'
                          : 'text-gray-700 hover:bg-gray-100' }}">
                <svg class="w-5 h-5 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" />
                </svg>
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>
    <div class="p-4 border-t border-gray-200 text-xs text-gray-500">
        @if (function_exists('tenant') && tenant())
            {{ tenant('name') }}
        @endif
    </div>
</aside>
