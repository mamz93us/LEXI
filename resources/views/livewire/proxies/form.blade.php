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

            {{-- ===== File upload + AI extraction ===== --}}
            <div class="border-t pt-4">
                <h3 class="text-sm font-semibold text-gray-900 mb-2">
                    صورة التوكيل الأصلي (اختياري)
                </h3>
                <p class="text-xs text-gray-500 mb-3">
                    ارفع صورة (JPG/PNG) أو ملف PDF للتوكيل الأصلي. سيقرأه المساعد القانوني ويستخرج البيانات تلقائياً (اسم الموكّل والوكيل، الرقم القومي، رقم التوثيق، نطاق التوكيل) لتستخدمها في الصياغة التالية.
                </p>

                @if ($proxy && $proxy->file_path)
                    <div class="mb-3 p-3 bg-gray-50 border rounded-md text-sm">
                        <div class="flex justify-between items-start">
                            <div>
                                <div class="font-medium text-gray-900">📎 ملف مرفق</div>
                                <div class="text-xs text-gray-500 mt-1">{{ basename($proxy->file_path) }}</div>
                            </div>
                            @php
                                $extractStatusLabels = [
                                    'pending' => ['ينتظر الاستخراج…', 'bg-blue-100 text-blue-800 animate-pulse'],
                                    'extracting' => ['جاري قراءة الوثيقة…', 'bg-blue-100 text-blue-800 animate-pulse'],
                                    'extracted' => ['تم استخراج البيانات ✓', 'bg-green-100 text-green-800'],
                                    'failed' => ['فشل الاستخراج', 'bg-red-100 text-red-800'],
                                ];
                                $stat = $extractStatusLabels[$proxy->extraction_status] ?? null;
                            @endphp
                            @if ($stat)
                                <span class="text-xs px-2 py-1 rounded {{ $stat[1] }}">{{ $stat[0] }}</span>
                            @endif
                        </div>

                        @if ($proxy->extraction_status === 'extracted' && is_array($proxy->extracted_data))
                            <div class="mt-3 pt-3 border-t text-xs">
                                <p class="font-medium text-gray-700 mb-1">البيانات المستخرجة:</p>
                                @if (! empty($proxy->extracted_data['parties']['principal']['name']))
                                    <p>الموكِّل: <strong>{{ $proxy->extracted_data['parties']['principal']['name'] }}</strong>
                                        @if (! empty($proxy->extracted_data['parties']['principal']['national_id']))
                                            ({{ $proxy->extracted_data['parties']['principal']['national_id'] }})
                                        @endif
                                    </p>
                                @endif
                                @if (! empty($proxy->extracted_data['parties']['agent']['name']))
                                    <p>الوكيل: <strong>{{ $proxy->extracted_data['parties']['agent']['name'] }}</strong>
                                        @if (! empty($proxy->extracted_data['parties']['agent']['national_id']))
                                            ({{ $proxy->extracted_data['parties']['agent']['national_id'] }})
                                        @endif
                                    </p>
                                @endif
                                @if (! empty($proxy->extracted_data['proxy']['notary_office']))
                                    <p>مكتب التوثيق: {{ $proxy->extracted_data['proxy']['notary_office'] }}</p>
                                @endif
                                @if (! empty($proxy->extracted_data['subject_property']))
                                    <p>موضوع التوكيل: {{ $proxy->extracted_data['subject_property'] }}</p>
                                @endif
                            </div>
                        @elseif ($proxy->extraction_status === 'failed' && $proxy->extracted_text)
                            <div class="mt-3 pt-3 border-t">
                                <p class="text-xs text-red-700 font-mono whitespace-pre-wrap">{{ $proxy->extracted_text }}</p>
                            </div>
                        @endif
                    </div>
                @endif

                <input type="file" wire:model="upload"
                       accept="application/pdf,image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-gray-700 file:me-3 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:bg-lexa-50 file:text-lexa-700 hover:file:bg-lexa-100" />
                <div wire:loading wire:target="upload" class="mt-1 text-xs text-gray-500">جاري رفع الملف…</div>
                @error('upload') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                @if ($upload)
                    <p class="mt-1 text-xs text-green-700">✓ تم اختيار: {{ $upload->getClientOriginalName() }} ({{ round($upload->getSize() / 1024) }} KB) — سيُحلل بعد الحفظ.</p>
                @endif
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
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
