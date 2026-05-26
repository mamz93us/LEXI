<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">الملكية الفكرية</h2>
            <a href="{{ route('ip-assets.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md transition">
                إضافة أصل
            </a>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->assets->isEmpty())
                <p class="p-6 text-center text-gray-500">لا توجد علامات تجارية أو براءات بعد.</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-start">النوع</th>
                            <th class="px-6 py-3 text-start">العنوان</th>
                            <th class="px-6 py-3 text-start">المالك</th>
                            <th class="px-6 py-3 text-start">رقم المكتب</th>
                            <th class="px-6 py-3 text-start">التجديد</th>
                            <th class="px-6 py-3 text-start">الحالة</th>
                            <th class="px-6 py-3 text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->assets as $a)
                            <tr>
                                <td class="px-6 py-3 text-sm">
                                    {{ ['trademark' => 'علامة', 'patent' => 'براءة', 'copyright' => 'حقوق نشر'][$a->asset_type] ?? $a->asset_type }}
                                </td>
                                <td class="px-6 py-3 text-sm font-medium">{{ $a->title }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">
                                    {{ $a->company?->name_ar ?? $a->company?->name ?? $a->client?->name_ar ?? $a->client?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $a->office_serial ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $a->renewal_date?->format('Y-m-d') ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm">{{ $a->status }}</td>
                                <td class="px-6 py-3 text-end text-sm">
                                    <a href="{{ route('ip-assets.edit', $a) }}" wire:navigate
                                       class="text-lexa-600 hover:text-lexa-800">{{ __('Edit') }}</a>
                                    <button wire:click="delete({{ $a->id }})" wire:confirm="حذف؟"
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
