<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">الجلسات</h2>
            <p class="text-sm text-gray-500">إضافة جلسات تتم من صفحة القضية مباشرة.</p>
        </div>

        <div class="mb-4">
            <select wire:model.live="when" class="rounded-md border-gray-300 shadow-sm">
                <option value="upcoming">القادمة</option>
                <option value="past">السابقة</option>
                <option value="all">الكل</option>
            </select>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->hearings->isEmpty())
                <p class="p-6 text-center text-gray-500">لا توجد جلسات.</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-start">التاريخ</th>
                            <th class="px-6 py-3 text-start">القضية</th>
                            <th class="px-6 py-3 text-start">المحكمة</th>
                            <th class="px-6 py-3 text-start">الغرض</th>
                            <th class="px-6 py-3 text-start">الطلبات</th>
                            <th class="px-6 py-3 text-start">الجلسة القادمة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->hearings as $h)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium">{{ $h->session_date->format('Y-m-d') }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <a href="{{ route('cases.show', $h->case) }}" wire:navigate
                                       class="text-lexa-700 hover:text-lexa-900">{{ $h->case?->case_number }}</a>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $h->court?->name_ar ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $h->purpose ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $h->requests->count() }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $h->next_date?->format('Y-m-d') ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
