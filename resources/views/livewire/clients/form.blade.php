<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">
            {{ $client ? __('Edit').' — '.($client->name_ar ?? $client->name) : __('Add client') }}
        </h2>

        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Type') }}</label>
                <select wire:model="type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500">
                    <option value="individual">{{ __('Individual') }}</option>
                    <option value="company">{{ __('Company') }}</option>
                    <option value="vip">VIP</option>
                </select>
                @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                <input wire:model="name" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">الاسم بالعربية</label>
                <input wire:model="name_ar" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                @error('name_ar') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Phone') }}</label>
                <input wire:model="phone" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                <input wire:model="email" type="email" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('clients.index') }}" wire:navigate
                   class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit"
                        class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
