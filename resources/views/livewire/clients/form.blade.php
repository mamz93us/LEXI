<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-1">
            {{ $client ? __('Edit').' — '.($client->name_ar ?? $client->name) : __('Add client') }}
        </h2>
        <p class="text-sm text-gray-500 mb-6">
            البيانات التي تُدخلها هنا تُستخدم تلقائياً في قوالب العقود عند اختيار هذا العميل كطرف (بائع / مشتري / موكِّل …).
        </p>

        <form wire:submit="save" class="space-y-5">

            {{-- ===== Identity ===== --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Type') }}</label>
                    <select wire:model="type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500">
                        <option value="individual">{{ __('Individual') }}</option>
                        <option value="company">{{ __('Company') }}</option>
                        <option value="vip">VIP</option>
                    </select>
                    @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Name') }} (EN)</label>
                    <input wire:model="name" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                    @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">الاسم بالعربية <span class="text-xs text-gray-500">(يُستخدم في العقود)</span></label>
                <input wire:model="name_ar" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                @error('name_ar') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ===== Contact ===== --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Phone') }}</label>
                    <input wire:model="phone" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                    @error('phone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">WhatsApp</label>
                    <input wire:model="whatsapp_phone" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">{{ __('Email') }}</label>
                    <input wire:model="email" type="email" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                    @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- ===== Egyptian legal fields ===== --}}
            <div class="border-t pt-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-3">بيانات قانونية مصرية</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">الرقم القومي</label>
                        <input wire:model="national_id" type="text" placeholder="14 رقم" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                        @error('national_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">السجل التجاري</label>
                        <input wire:model="commercial_register_no" type="text" placeholder="للشركات فقط" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">الجنسية</label>
                        <input wire:model="nationality" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">الديانة</label>
                        <select wire:model="religion" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500">
                            <option value="">—</option>
                            <option value="مسلم">مسلم</option>
                            <option value="مسيحي">مسيحي</option>
                            <option value="يهودي">يهودي</option>
                            <option value="بهائي">بهائي</option>
                            <option value="أخرى">أخرى</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">المهنة</label>
                        <input wire:model="profession" type="text" placeholder="مثال: مهندس، طبيب، تاجر" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">تاريخ الميلاد</label>
                        <input wire:model="date_of_birth" type="date" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">العنوان</label>
                <textarea wire:model="address" rows="2" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">ملاحظات</label>
                <textarea wire:model="notes" rows="2" class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <a href="{{ route('clients.index') }}" wire:navigate
                   class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit"
                        class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
