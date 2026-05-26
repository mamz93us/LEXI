<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">
            {{ $template ? 'تعديل قالب: '.$template->title : 'قالب جديد' }}
        </h2>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-gray-700">العنوان</label>
                    <input wire:model="title" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">النوع</label>
                    <select wire:model="type" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="contract">عقد</option>
                        <option value="poa">توكيل</option>
                        <option value="memo">مذكرة</option>
                        <option value="filing">إيداع</option>
                        <option value="other">أخرى</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">الوصف</label>
                <input wire:model="description" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-[1fr_18rem] gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">جسم القالب</label>
                    <p class="text-xs text-gray-500 mb-1">
                        انقر على أي متغير من القائمة على اليسار لإدراجه في موضع المؤشر، أو اكتب يدوياً
                        <code class="bg-gray-100 px-1 rounded">@{{seller.name}}</code> أو <code class="bg-gray-100 px-1 rounded">@{{contract.place}}</code>.
                    </p>
                    <textarea wire:model="body" rows="22"
                              class="mt-1 w-full rounded-md border-gray-300 shadow-sm font-mono text-sm leading-7" dir="rtl"></textarea>
                    @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <x-variable-chips target="body" />
                </div>
            </div>

            <details class="border rounded-md">
                <summary class="px-3 py-2 text-sm font-medium text-gray-700 cursor-pointer bg-gray-50">
                    متغيرات إضافية مخصصة (JSON، اختياري)
                </summary>
                <div class="p-3">
                    <p class="text-xs text-gray-500 mb-1">
                        تُضاف هنا فقط الحقول الإضافية التي لا تغطيها قائمة المتغيرات الجاهزة. مثال:
                    </p>
                    <pre class="bg-gray-100 px-2 py-1 rounded text-xs overflow-x-auto" dir="ltr">[@{{"name":"property_address","label_ar":"عنوان العقار","type":"text","required":true@}}]</pre>
                    <textarea wire:model="variables_json" rows="5"
                              class="mt-1 w-full rounded-md border-gray-300 shadow-sm font-mono text-xs"></textarea>
                    @error('variables_json') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </details>

            <div class="flex items-center gap-2">
                <input wire:model="is_active" type="checkbox" id="is_active" class="rounded border-gray-300" />
                <label for="is_active" class="text-sm">قالب نشط (متاح للاستخدام)</label>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('templates.index') }}" wire:navigate
                   class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit"
                        class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">
                    حفظ كإصدار جديد
                </button>
            </div>
        </form>
    </div>
</div>
