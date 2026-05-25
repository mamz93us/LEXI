<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">{{ __('Cases') }}</h2>
            <a href="{{ route('cases.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md transition">
                {{ __('Add case') }}
            </a>
        </div>

        <div class="mb-4">
            <input type="text"
                   wire:model.live.debounce.250ms="search"
                   placeholder="{{ __('Search') }}"
                   class="w-full sm:w-80 rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->cases->isEmpty())
                <p class="p-6 text-center text-gray-500">{{ __('No cases yet') }}</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-start">{{ __('Case number') }}</th>
                            <th class="px-6 py-3 text-start">{{ __('Client') }}</th>
                            <th class="px-6 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->cases as $case)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-900">
                                    {{ $case->case_number }}
                                    <span class="block text-xs text-gray-500">{{ $case->title }}</span>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500">
                                    {{ $case->client?->name_ar ?? $case->client?->name }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500">
                                    {{ __(ucfirst($case->status)) }}
                                </td>
                                <td class="px-6 py-3 text-end text-sm">
                                    <a href="{{ route('cases.edit', $case) }}" wire:navigate
                                       class="text-lexa-600 hover:text-lexa-800">{{ __('Edit') }}</a>
                                    <button type="button"
                                            wire:click="delete({{ $case->id }})"
                                            wire:confirm="حذف هذه القضية؟"
                                            class="ms-3 text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
