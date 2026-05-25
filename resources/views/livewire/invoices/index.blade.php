<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">الفواتير</h2>
            <a href="{{ route('invoices.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md transition">
                إنشاء فاتورة
            </a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="bg-white shadow-sm rounded-lg p-4">
                <p class="text-xs text-gray-500">المعلق</p>
                <p class="text-xl font-semibold text-gray-900">{{ number_format($this->totalsPiastres['outstanding'] / 100, 2) }} ج.م</p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-4">
                <p class="text-xs text-gray-500">المدفوع</p>
                <p class="text-xl font-semibold text-green-700">{{ number_format($this->totalsPiastres['paid'] / 100, 2) }} ج.م</p>
            </div>
            <div class="bg-white shadow-sm rounded-lg p-4">
                <p class="text-xs text-gray-500">متأخر</p>
                <p class="text-xl font-semibold text-red-700">{{ number_format($this->totalsPiastres['overdue'] / 100, 2) }} ج.م</p>
            </div>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->invoices->isEmpty())
                <p class="p-6 text-center text-gray-500">لا توجد فواتير بعد.</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-start">الرقم</th>
                            <th class="px-6 py-3 text-start">{{ __('Client') }}</th>
                            <th class="px-6 py-3 text-start">الإصدار</th>
                            <th class="px-6 py-3 text-start">{{ __('Status') }}</th>
                            <th class="px-6 py-3 text-end">المبلغ</th>
                            <th class="px-6 py-3 text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->invoices as $invoice)
                            <tr>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $invoice->number }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $invoice->client?->name_ar ?? $invoice->client?->name }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $invoice->issue_date->format('Y-m-d') }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $invoice->status }}</td>
                                <td class="px-6 py-3 text-sm text-end">{{ number_format($invoice->total_piastres / 100, 2) }} ج.م</td>
                                <td class="px-6 py-3 text-end text-sm">
                                    <a href="{{ route('invoices.edit', $invoice) }}" wire:navigate
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
