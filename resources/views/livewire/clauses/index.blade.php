<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">مكتبة البنود</h2>
                <p class="text-sm text-gray-500 mt-1">بنود معتمدة تُدرج حرفياً في العقود الآلية — لا يجوز للمساعد الآلي تعديلها.</p>
            </div>
            <a href="{{ route('clauses.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md transition">
                بند جديد
            </a>
        </div>

        <div class="flex gap-3 mb-4">
            <input type="text" wire:model.live.debounce.250ms="search" placeholder="{{ __('Search') }}"
                   class="flex-1 rounded-md border-gray-300 shadow-sm" />
            <select wire:model.live="topic" class="rounded-md border-gray-300 shadow-sm">
                <option value="">كل الموضوعات</option>
                @foreach ($this->topics as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </select>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->clauses->isEmpty())
                <p class="p-6 text-center text-gray-500">لا توجد بنود بعد. ابدأ بإضافة بنود الاختصاص القضائي، التحكيم، السرية، الجزاءات…</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-start">الموضوع</th>
                            <th class="px-6 py-3 text-start">العنوان</th>
                            <th class="px-6 py-3 text-start">الإصدار</th>
                            <th class="px-6 py-3 text-start">الحالة</th>
                            <th class="px-6 py-3 text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->clauses as $c)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $c->topic }}</td>
                                <td class="px-6 py-3 text-sm font-medium">{{ $c->title }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">v{{ $c->currentVersion?->version_no ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm">
                                    @if ($c->currentVersion?->approved_at)
                                        <span class="text-xs px-2 py-1 rounded bg-green-100 text-green-800">معتمد</span>
                                    @else
                                        <span class="text-xs px-2 py-1 rounded bg-amber-100 text-amber-800">قيد المراجعة</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-end text-sm">
                                    <a href="{{ route('clauses.edit', $c) }}" wire:navigate
                                       class="text-lexa-600 hover:text-lexa-800">{{ __('Edit') }}</a>
                                    <button wire:click="delete({{ $c->id }})" wire:confirm="حذف هذا البند؟"
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
