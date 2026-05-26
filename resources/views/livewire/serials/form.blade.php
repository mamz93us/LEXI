<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">{{ $serial ? 'تعديل ورقة' : 'ورقة حكومية جديدة' }}</h2>
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">رقم المسلسل</label>
                    <input wire:model="serial_no" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">اسم المستند</label>
                    <input wire:model="document_name" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">الجهة المُصدِرة</label>
                <input wire:model="issuing_authority" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">ربط بـ</label>
                    <select wire:model.live="owner_kind" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="none">لا شيء</option>
                        <option value="case">قضية</option>
                        <option value="company">شركة</option>
                    </select>
                </div>
                @if ($owner_kind !== 'none')
                    <div>
                        <label class="block text-sm font-medium text-gray-700">اختر</label>
                        <select wire:model="owner_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">—</option>
                            @foreach ($this->ownerOptions as $opt)
                                <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">الرسوم (ج.م)</label>
                    <input wire:model="fees_egp" type="number" min="0" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">تاريخ الإصدار</label>
                    <input wire:model="issued_at" type="date" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">الحالة</label>
                    <select wire:model="status" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="pending">قيد الإصدار</option>
                        <option value="issued">صدرت</option>
                        <option value="collected">استلمت</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('serials.index') }}" wire:navigate class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit" class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
