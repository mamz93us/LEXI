<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">الامتثال</h2>
            <a href="{{ route('compliance.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md transition">
                التزام جديد
            </a>
        </div>

        <div class="mb-4">
            <select wire:model.live="status" class="rounded-md border-gray-300 shadow-sm">
                <option value="">كل الحالات</option>
                <option value="open">مفتوح</option>
                <option value="done">منجز</option>
                <option value="overdue">متأخر</option>
            </select>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->items->isEmpty())
                <p class="p-6 text-center text-gray-500">لا توجد التزامات بعد. أضف تجديدات السجل التجاري، الإقرارات الضريبية، الجمعيات السنوية…</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-start">الشركة</th>
                            <th class="px-6 py-3 text-start">النوع</th>
                            <th class="px-6 py-3 text-start">الاستحقاق</th>
                            <th class="px-6 py-3 text-start">التكرار</th>
                            <th class="px-6 py-3 text-start">الحالة</th>
                            <th class="px-6 py-3 text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->items as $it)
                            <tr class="{{ $it->isOverdue() ? 'bg-red-50' : '' }}">
                                <td class="px-6 py-3 text-sm font-medium">{{ $it->company?->name_ar ?? $it->company?->name }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $it->title ?? $it->type }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <span class="{{ $it->isOverdue() ? 'text-red-700 font-semibold' : 'text-gray-500' }}">
                                        {{ $it->due_date->format('Y-m-d') }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $it->recurrence ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <span class="text-xs px-2 py-1 rounded
                                        {{ $it->status === 'done' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $it->status === 'open' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $it->status === 'overdue' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ ['done' => 'منجز', 'open' => 'مفتوح', 'overdue' => 'متأخر'][$it->status] ?? $it->status }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-end text-sm">
                                    @if ($it->status !== 'done')
                                        <button wire:click="markDone({{ $it->id }})" class="text-green-700 hover:text-green-900">إنجاز</button>
                                    @endif
                                    <a href="{{ route('compliance.edit', $it) }}" wire:navigate
                                       class="ms-3 text-lexa-600 hover:text-lexa-800">{{ __('Edit') }}</a>
                                    <button wire:click="delete({{ $it->id }})" wire:confirm="حذف هذا الالتزام؟"
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
