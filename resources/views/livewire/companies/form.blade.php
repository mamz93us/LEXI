<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">
            {{ $company ? 'تعديل '.($company->name_ar ?? $company->name) : 'إضافة شركة' }}
        </h2>

        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">{{ __('Client') }}</label>
                <select wire:model="client_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">—</option>
                    @foreach ($this->clients as $c)
                        <option value="{{ $c->id }}">{{ $c->name_ar ?? $c->name }}</option>
                    @endforeach
                </select>
                @error('client_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Name') }}</label>
                    <input wire:model="name" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">الاسم بالعربية</label>
                    <input wire:model="name_ar" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">الشكل القانوني</label>
                    <select wire:model="legal_form" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="llc">LLC — ذ.م.م</option>
                        <option value="jsc">JSC — مساهمة</option>
                        <option value="sole">منشأة فردية</option>
                        <option value="branch">فرع</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
                    <select wire:model="status" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="in_formation">قيد التأسيس</option>
                        <option value="active">نشطة</option>
                        <option value="suspended">معلقة</option>
                        <option value="dissolved">منحلّة</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">رأس المال (ج.م)</label>
                    <input wire:model="capital_egp" type="number" min="0" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">السجل التجاري</label>
                    <input wire:model="commercial_register_no" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">البطاقة الضريبية</label>
                    <input wire:model="tax_card_no" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">ملف الاستثمار GAFI</label>
                    <input wire:model="gafi_file_no" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('companies.index') }}" wire:navigate
                   class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit"
                        class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
