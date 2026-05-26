<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">قوالب العقود</h2>
            <a href="{{ route('templates.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md transition">
                قالب جديد
            </a>
        </div>

        <div class="mb-4">
            <input type="text" wire:model.live.debounce.250ms="search" placeholder="{{ __('Search') }}"
                   class="w-full sm:w-80 rounded-md border-gray-300 shadow-sm" />
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->templates->isEmpty())
                <p class="p-6 text-center text-gray-500">
                    لا توجد قوالب بعد. ابدأ بإضافة قالب لعقد متكرر — استخدم <code class="bg-gray-100 px-1 rounded">@{{name}}</code> للحقول المتغيرة.
                </p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-start">العنوان</th>
                            <th class="px-6 py-3 text-start">النوع</th>
                            <th class="px-6 py-3 text-start">الإصدار</th>
                            <th class="px-6 py-3 text-start">الحالة</th>
                            <th class="px-6 py-3 text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->templates as $t)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium">{{ $t->title }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $t->type }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">v{{ $t->currentVersion?->version_no ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm">
                                    @if ($t->is_active)
                                        <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-800">نشط</span>
                                    @else
                                        <span class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-600">معطل</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-end text-sm">
                                    <a href="{{ route('templates.edit', $t) }}" wire:navigate
                                       class="text-lexa-600 hover:text-lexa-800">{{ __('Edit') }}</a>
                                    <button wire:click="delete({{ $t->id }})" wire:confirm="حذف هذا القالب؟"
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
