<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">الأوراق الحكومية</h2>
            <a href="{{ route('serials.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md transition">
                ورقة جديدة
            </a>
        </div>

        <div class="mb-4">
            <select wire:model.live="status" class="rounded-md border-gray-300 shadow-sm">
                <option value="">كل الحالات</option>
                <option value="pending">قيد الإصدار</option>
                <option value="issued">صدرت</option>
                <option value="collected">استلمت</option>
            </select>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->serials->isEmpty())
                <p class="p-6 text-center text-gray-500">لا توجد أوراق بعد.</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-start">الرقم</th>
                            <th class="px-6 py-3 text-start">المستند</th>
                            <th class="px-6 py-3 text-start">الجهة</th>
                            <th class="px-6 py-3 text-start">الحالة</th>
                            <th class="px-6 py-3 text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->serials as $s)
                            <tr>
                                <td class="px-6 py-3 text-sm">{{ $s->serial_no }}</td>
                                <td class="px-6 py-3 text-sm font-medium">{{ $s->document_name }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $s->issuing_authority ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm">
                                    {{ ['pending' => 'قيد الإصدار', 'issued' => 'صدرت', 'collected' => 'استلمت'][$s->status] ?? $s->status }}
                                </td>
                                <td class="px-6 py-3 text-end text-sm">
                                    @if ($s->status !== 'collected')
                                        <button wire:click="markCollected({{ $s->id }})" class="text-green-700 hover:text-green-900">استلام</button>
                                    @endif
                                    <a href="{{ route('serials.edit', $s) }}" wire:navigate class="ms-3 text-lexa-600 hover:text-lexa-800">{{ __('Edit') }}</a>
                                    <button wire:click="delete({{ $s->id }})" wire:confirm="حذف؟" class="ms-3 text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
