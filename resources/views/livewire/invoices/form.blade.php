<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto bg-white shadow-sm rounded-lg p-6">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">
            {{ $invoice ? 'تعديل فاتورة '.$invoice->number : 'فاتورة جديدة' }}
        </h2>

        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
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
                <div>
                    <label class="block text-sm font-medium text-gray-700">الرقم</label>
                    <input wire:model="number" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">الإصدار</label>
                    <input wire:model="issue_date" type="date" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">الاستحقاق</label>
                    <input wire:model="due_date" type="date" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">{{ __('Status') }}</label>
                    <select wire:model="status" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="draft">مسودة</option>
                        <option value="sent">مرسلة</option>
                        <option value="partly_paid">مدفوعة جزئياً</option>
                        <option value="paid">مدفوعة</option>
                        <option value="void">ملغاة</option>
                    </select>
                </div>
            </div>

            <div class="border-t pt-4">
                <div class="flex justify-between mb-2">
                    <h3 class="font-medium text-gray-900">البنود</h3>
                    <button type="button" wire:click="addLine" class="text-sm text-lexa-700 hover:text-lexa-900">+ إضافة بند</button>
                </div>

                @foreach ($lines as $i => $line)
                    <div class="grid grid-cols-12 gap-2 mb-2" wire:key="line-{{ $i }}">
                        <input wire:model="lines.{{ $i }}.description" type="text" placeholder="الوصف" class="col-span-6 rounded-md border-gray-300 shadow-sm" />
                        <input wire:model="lines.{{ $i }}.quantity" type="number" step="0.01" min="0.01" class="col-span-2 rounded-md border-gray-300 shadow-sm" />
                        <input wire:model="lines.{{ $i }}.unit_price_egp" type="number" step="0.01" min="0" placeholder="السعر ج.م" class="col-span-3 rounded-md border-gray-300 shadow-sm" />
                        <button type="button" wire:click="removeLine({{ $i }})" class="col-span-1 text-red-600 text-sm">حذف</button>
                    </div>
                @endforeach
                @error('lines') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <a href="{{ route('invoices.index') }}" wire:navigate class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">{{ __('Cancel') }}</a>
                <button type="submit" class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">
                    {{ __('Save') }}
                </button>
            </div>
        </form>
    </div>
</div>
