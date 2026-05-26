<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">الوقت المسجل</h2>
            <a href="{{ route('time-entries.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md transition">
                تسجيل وقت
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div class="bg-white shadow-sm rounded-lg p-4">
                <p class="text-xs text-gray-500">إجمالي ساعات قابلة للفوترة</p>
                <p class="text-xl font-semibold text-gray-900">{{ $this->summary['total_hours'] }} ساعة</p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-4">
                <p class="text-xs text-gray-500">غير مفوتر</p>
                <p class="text-xl font-semibold text-amber-700">{{ $this->summary['unbilled_hours'] }} ساعة</p>
            </div>
        </div>

        <div class="mb-4">
            <select wire:model.live="billable" class="rounded-md border-gray-300 shadow-sm">
                <option value="">الكل</option>
                <option value="1">قابل للفوترة</option>
                <option value="0">غير قابل للفوترة</option>
            </select>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->entries->isEmpty())
                <p class="p-6 text-center text-gray-500">لا توجد مدخلات وقت بعد.</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-start">التاريخ</th>
                            <th class="px-6 py-3 text-start">المحامي</th>
                            <th class="px-6 py-3 text-start">المرتبط</th>
                            <th class="px-6 py-3 text-start">الدقائق</th>
                            <th class="px-6 py-3 text-start">الوصف</th>
                            <th class="px-6 py-3 text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->entries as $e)
                            <tr>
                                <td class="px-6 py-3 text-sm">{{ $e->worked_on->format('Y-m-d') }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $e->user?->name }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">
                                    @if ($e->subject)
                                        {{ class_basename($e->subject_type) }} #{{ $e->subject_id }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm font-medium">{{ $e->minutes }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ \Illuminate\Support\Str::limit($e->description ?? '', 60) }}</td>
                                <td class="px-6 py-3 text-end text-sm">
                                    <a href="{{ route('time-entries.edit', $e) }}" wire:navigate
                                       class="text-lexa-600 hover:text-lexa-800">{{ __('Edit') }}</a>
                                    <button wire:click="delete({{ $e->id }})" wire:confirm="حذف هذا السجل؟"
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
