<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">الشركات</h2>
            <a href="{{ route('companies.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md transition">
                إضافة شركة
            </a>
        </div>

        <div class="mb-4">
            <input type="text"
                   wire:model.live.debounce.250ms="search"
                   placeholder="{{ __('Search') }}"
                   class="w-full sm:w-80 rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->companies->isEmpty())
                <p class="p-6 text-center text-gray-500">لا توجد شركات بعد.</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-start">{{ __('Name') }}</th>
                            <th class="px-6 py-3 text-start">الشكل القانوني</th>
                            <th class="px-6 py-3 text-start">السجل التجاري</th>
                            <th class="px-6 py-3 text-start">خطوات معلقة</th>
                            <th class="px-6 py-3 text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->companies as $company)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-900">
                                    {{ $company->name_ar ?? $company->name }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ strtoupper($company->legal_form) }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $company->commercial_register_no ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $company->pending_steps_count }}</td>
                                <td class="px-6 py-3 text-end text-sm">
                                    <a href="{{ route('companies.edit', $company) }}" wire:navigate
                                       class="text-lexa-600 hover:text-lexa-800">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
