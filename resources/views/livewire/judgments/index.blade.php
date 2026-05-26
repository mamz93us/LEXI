<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">الأحكام</h2>
            <p class="text-sm text-gray-500">إدخال أحكام يتم من صفحة القضية مباشرة.</p>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->judgments->isEmpty())
                <p class="p-6 text-center text-gray-500">لا توجد أحكام بعد.</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-start">تاريخ الحكم</th>
                            <th class="px-6 py-3 text-start">القضية</th>
                            <th class="px-6 py-3 text-start">النوع</th>
                            <th class="px-6 py-3 text-start">الحضور</th>
                            <th class="px-6 py-3 text-start">ميعاد الطعن</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->judgments as $j)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium">{{ $j->judgment_date->format('Y-m-d') }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <a href="{{ route('cases.show', $j->case) }}" wire:navigate
                                       class="text-lexa-700 hover:text-lexa-900">{{ $j->case?->case_number }}</a>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $j->judgmentType?->name_ar ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">
                                    {{ $j->presence_type === 'in_presence' ? 'حضوري' : 'غيابي' }}
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    @if ($j->appeal_deadline)
                                        <span class="text-amber-700 font-medium">{{ $j->appeal_deadline->format('Y-m-d') }}</span>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
