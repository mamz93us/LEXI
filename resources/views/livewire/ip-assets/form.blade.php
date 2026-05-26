<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">{{ $asset ? 'تعديل أصل' : 'أصل ملكية فكرية جديد' }}</h2>
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">النوع</label>
                    <select wire:model="asset_type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="trademark">علامة تجارية</option>
                        <option value="patent">براءة اختراع</option>
                        <option value="copyright">حقوق نشر</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">الحالة</label>
                    <select wire:model="status" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="pending">قيد التسجيل</option>
                        <option value="active">سارٍ</option>
                        <option value="expired">منتهٍ</option>
                        <option value="abandoned">متروك</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">العنوان</label>
                <input wire:model="title" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">الشركة المالكة</label>
                    <select wire:model="company_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">—</option>
                        @foreach ($this->companies as $c) <option value="{{ $c->id }}">{{ $c->name_ar ?? $c->name }}</option> @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">العميل المالك</label>
                    <select wire:model="client_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">—</option>
                        @foreach ($this->clients as $c) <option value="{{ $c->id }}">{{ $c->name_ar ?? $c->name }}</option> @endforeach
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">التصنيف (Nice/IPC)</label>
                    <input wire:model="classification" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">رقم مكتب التسجيل</label>
                    <input wire:model="office_serial" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">تاريخ الإيداع</label>
                    <input wire:model="filed_on" type="date" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">تاريخ المنح</label>
                    <input wire:model="granted_on" type="date" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">تاريخ التجديد</label>
                    <input wire:model="renewal_date" type="date" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('ip-assets.index') }}" wire:navigate class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit" class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
