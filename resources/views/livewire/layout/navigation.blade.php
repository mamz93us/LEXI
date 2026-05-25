<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100 h-16">
    <div class="max-w-full mx-auto px-4 sm:px-6 lg:px-8 h-full">
        <div class="flex justify-between h-full items-center">
            <div class="text-sm text-gray-500">
                @auth
                    {{ tenant('name') ?? config('app.name') }}
                @endauth
            </div>

            <div class="flex items-center gap-4">
                <x-lang-switcher />

                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 text-sm font-medium rounded-md text-gray-500 hover:text-gray-700 transition">
                                <span x-data="{{ json_encode(['name' => auth()->user()->name]) }}"
                                      x-text="name"
                                      x-on:profile-updated.window="name = $event.detail.name"></span>
                                <svg class="fill-current h-4 w-4 ms-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile')" wire:navigate>{{ __('Profile') }}</x-dropdown-link>
                            <button wire:click="logout" class="w-full text-start">
                                <x-dropdown-link>{{ __('Log Out') }}</x-dropdown-link>
                            </button>
                        </x-slot>
                    </x-dropdown>
                @endauth
            </div>
        </div>
    </div>
</nav>
