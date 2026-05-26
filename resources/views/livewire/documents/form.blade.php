<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">
            {{ $document ? 'تعديل المستند' : 'رفع مستند جديد' }}
        </h2>

        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">العنوان</label>
                <input wire:model="title" type="text"
                       class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <div>
                    <label class="block text-sm font-medium text-gray-700">ربط بـ</label>
                    <select wire:model.live="owner_kind" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="none">لا شيء</option>
                        <option value="case">قضية</option>
                        <option value="company">شركة</option>
                        <option value="client">عميل</option>
                    </select>
                </div>
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

            <div>
                <label class="block text-sm font-medium text-gray-700">
                    {{ $document ? 'إصدار جديد (اختياري)' : 'الملف' }}
                </label>
                <input wire:model="file" type="file"
                       class="mt-1 w-full text-sm" />
                <p class="mt-1 text-xs text-gray-500">PDF / DOCX / صور / TXT — حد أقصى 25 ميجابايت.</p>
                @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                <div wire:loading wire:target="file" class="mt-2 text-sm text-gray-500">جاري الرفع...</div>
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('documents.index') }}" wire:navigate
                   class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit"
                        class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
