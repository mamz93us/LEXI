<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">
            {{ $entry ? 'تعديل سجل وقت' : 'تسجيل وقت' }}
        </h2>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">التاريخ</label>
                    <input wire:model="worked_on" type="date" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">الدقائق</label>
                    <input wire:model="minutes" type="number" min="1" max="1440" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">السعر/ساعة (ج.م)</label>
                    <input wire:model="rate_egp_per_hour" type="number" min="0" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">الوصف</label>
                <textarea wire:model="description" rows="3" class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">ربط بـ</label>
                    <select wire:model.live="subject_kind" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="none">لا شيء</option>
                        <option value="case">قضية</option>
                        <option value="company">شركة</option>
                        <option value="client">عميل</option>
                    </select>
                </div>
                @if ($subject_kind !== 'none')
                    <div>
                        <label class="block text-sm font-medium text-gray-700">اختر</label>
                        <select wire:model="subject_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">—</option>
                            @foreach ($this->subjects as $opt)
                                <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            <label class="inline-flex items-center gap-2 text-sm">
                <input wire:model="billable" type="checkbox" class="rounded border-gray-300" />
                قابل للفوترة
            </label>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('time-entries.index') }}" wire:navigate
                   class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit"
                        class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
