<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">{{ $item ? 'تعديل التزام' : 'التزام جديد' }}</h2>
        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">الشركة</label>
                <select wire:model="company_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">—</option>
                    @foreach ($this->companies as $c)
                        <option value="{{ $c->id }}">{{ $c->name_ar ?? $c->name }}</option>
                    @endforeach
                </select>
                @error('company_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">النوع</label>
                    <select wire:model="type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="cr_renewal">تجديد سجل تجاري</option>
                        <option value="vat">إقرار ضريبة قيمة مضافة</option>
                        <option value="tax">إقرار ضريبي</option>
                        <option value="social_insurance">تأمينات اجتماعية</option>
                        <option value="agm">جمعية عمومية سنوية</option>
                        <option value="auditor">تعيين مراجع</option>
                        <option value="license">ترخيص مزاولة</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">التكرار</label>
                    <select wire:model="recurrence" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">—</option>
                        <option value="monthly">شهري</option>
                        <option value="quarterly">ربع سنوي</option>
                        <option value="annual">سنوي</option>
                        <option value="one_off">لمرة واحدة</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">عنوان مخصص (اختياري)</label>
                <input wire:model="title" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">تاريخ الاستحقاق</label>
                <input wire:model="due_date" type="date" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                @error('due_date') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">ملاحظات</label>
                <textarea wire:model="notes" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('compliance.index') }}" wire:navigate class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit" class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
