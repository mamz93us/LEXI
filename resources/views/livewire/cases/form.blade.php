<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">
            {{ $case ? __('Edit').' — '.$case->case_number : __('Add case') }}
        </h2>

        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Client') }}</label>
                <select wire:model="client_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500">
                    <option value="">—</option>
                    @foreach ($this->clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name_ar ?? $client->name }}</option>
                    @endforeach
                </select>
                @error('client_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Case number') }}</label>
                <input wire:model="case_number" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                @error('case_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                <input wire:model="title" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
                <select wire:model="status" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500">
                    <option value="open">{{ __(ucfirst('open')) }}</option>
                    <option value="on_hold">On hold</option>
                    <option value="closed">{{ __(ucfirst('closed')) }}</option>
                </select>
                @error('status') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('cases.index') }}" wire:navigate
                   class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit"
                        class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
