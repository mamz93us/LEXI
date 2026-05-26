<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">التوكيلات</h2>
            <a href="{{ route('proxies.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md transition">
                توكيل جديد
            </a>
        </div>

        <div class="flex gap-3 mb-4">
            <input type="text" wire:model.live.debounce.250ms="search" placeholder="بحث برقم التوثيق أو الموكّل..."
                   class="flex-1 rounded-md border-gray-300 shadow-sm" />
            <select wire:model.live="status" class="rounded-md border-gray-300 shadow-sm">
                <option value="">كل الحالات</option>
                <option value="valid">سارٍ</option>
                <option value="expiring">قارب على الانتهاء</option>
                <option value="expired">منتهٍ</option>
                <option value="revoked">ملغى</option>
            </select>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->proxies->isEmpty())
                <p class="p-6 text-center text-gray-500">لا توجد توكيلات بعد.</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-start">الموكّل</th>
                            <th class="px-6 py-3 text-start">النوع</th>
                            <th class="px-6 py-3 text-start">رقم التوثيق</th>
                            <th class="px-6 py-3 text-start">الإصدار</th>
                            <th class="px-6 py-3 text-start">الانتهاء</th>
                            <th class="px-6 py-3 text-start">الحالة</th>
                            <th class="px-6 py-3 text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->proxies as $p)
                            <tr class="{{ $p->isExpired() ? 'bg-red-50' : '' }}">
                                <td class="px-6 py-3 text-sm font-medium">{{ $p->client?->name_ar ?? $p->client?->name }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $p->type === 'general' ? 'عام' : 'خاص' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $p->notary_serial ?? '—' }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $p->issue_date->format('Y-m-d') }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $p->expiry_date?->format('Y-m-d') ?? 'غير محدد' }}</td>
                                <td class="px-6 py-3 text-sm">
                                    <span class="text-xs px-2 py-1 rounded
                                        {{ $p->status === 'valid' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $p->status === 'expiring' ? 'bg-amber-100 text-amber-800' : '' }}
                                        {{ $p->status === 'expired' ? 'bg-red-100 text-red-800' : '' }}
                                        {{ $p->status === 'revoked' ? 'bg-gray-200 text-gray-700' : '' }}">
                                        {{ ['valid' => 'سارٍ', 'expiring' => 'قارب', 'expired' => 'منتهٍ', 'revoked' => 'ملغى'][$p->status] }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-end text-sm">
                                    <a href="{{ route('proxies.edit', $p) }}" wire:navigate
                                       class="text-lexa-600 hover:text-lexa-800">{{ __('Edit') }}</a>
                                    <button wire:click="delete({{ $p->id }})" wire:confirm="حذف هذا التوكيل؟"
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
