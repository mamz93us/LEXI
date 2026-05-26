<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">
            {{ $clause ? 'تعديل بند: '.$clause->title : 'بند جديد' }}
        </h2>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">الموضوع</label>
                    <input wire:model="topic" type="text" placeholder="arbitration / governing_law / penalties / confidentiality ..."
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                    @error('topic') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">العنوان</label>
                    <input wire:model="title" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                    @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">نص البند (يُدرج حرفياً في العقد)</label>
                <p class="text-xs text-gray-500 mb-2">
                    اكتب <code class="bg-gray-100 px-1 rounded">@{{</code> لظهور قائمة المتغيرات (مثل البائع، المحكمة، إلخ). <kbd class="px-1 py-0.5 bg-gray-100 border rounded text-[10px]">Ctrl</kbd>+<kbd class="px-1 py-0.5 bg-gray-100 border rounded text-[10px]">Z</kbd> للتراجع.
                </p>
                <x-template-editor wire-key="body" :initial="$body" :rows="18" />
                @error('body') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-6">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input wire:model="is_active" type="checkbox" class="rounded border-gray-300" />
                    نشط
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input wire:model="approve_now" type="checkbox" class="rounded border-gray-300" />
                    اعتمد هذا الإصدار الآن (للشركاء والمديرين فقط)
                </label>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('clauses.index') }}" wire:navigate
                   class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit"
                        class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">
                    حفظ كإصدار جديد
                </button>
            </div>
        </form>
    </div>
</div>
