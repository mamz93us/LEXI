<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">
            {{ $proxy ? 'تعديل توكيل' : 'توكيل جديد' }}
        </h2>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">الموكّل</label>
                    <select wire:model="client_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">—</option>
                        @foreach ($this->clients as $c)
                            <option value="{{ $c->id }}">{{ $c->name_ar ?? $c->name }}</option>
                        @endforeach
                    </select>
                    @error('client_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">نوع التوكيل</label>
                    <select wire:model="type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="specific">خاص</option>
                        <option value="general">عام</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">رقم التوثيق</label>
                    <input wire:model="notary_serial" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">تاريخ الإصدار</label>
                    <input wire:model="issue_date" type="date" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">تاريخ الانتهاء</label>
                    <input wire:model="expiry_date" type="date" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">نطاق التوكيل</label>
                <textarea wire:model="scope" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">الحالة</label>
                <select wire:model="status" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                    <option value="valid">سارٍ</option>
                    <option value="expiring">قارب على الانتهاء</option>
                    <option value="expired">منتهٍ</option>
                    <option value="revoked">ملغى</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">المحامون المفوّضون</label>
                <select wire:model="lawyer_ids" multiple class="mt-1 w-full rounded-md border-gray-300 shadow-sm h-32">
                    @foreach ($this->lawyers as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">القضايا المرتبطة</label>
                <select wire:model="case_ids" multiple class="mt-1 w-full rounded-md border-gray-300 shadow-sm h-32">
                    @foreach ($this->casesList as $c)
                        <option value="{{ $c->id }}">{{ $c->case_number }} — {{ $c->title }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('proxies.index') }}" wire:navigate
                   class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit"
                        class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
