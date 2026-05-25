<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-6">
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900">
                    @auth
                        {{ __('Welcome') ?? 'Welcome' }}, {{ auth()->user()->name }}
                    @endauth
                </h3>
                <p class="mt-2 text-sm text-gray-600">
                    {{ tenant('name') }} — {{ tenant('id') }}
                </p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <a href="{{ route('clients.index') }}" wire:navigate
                   class="block bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition">
                    <h4 class="font-semibold text-lexa-700">{{ __('Clients') }}</h4>
                    <p class="text-sm text-gray-500 mt-1">{{ __('Add client') }}</p>
                </a>

                <a href="{{ route('cases.index') }}" wire:navigate
                   class="block bg-white shadow-sm rounded-lg p-6 hover:shadow-md transition">
                    <h4 class="font-semibold text-lexa-700">{{ __('Cases') }}</h4>
                    <p class="text-sm text-gray-500 mt-1">{{ __('Add case') }}</p>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
