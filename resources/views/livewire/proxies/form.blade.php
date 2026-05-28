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
                            @php
                                $extractedProxy = $proxy->extracted_data['proxy'] ?? [];
                                $extractedParties = $proxy->extracted_data['parties'] ?? [];
                                $extractedWitnesses = $proxy->extracted_data['witnesses'] ?? [];
                                $extractedSubject = $proxy->extracted_data['subject_property'] ?? null;
                                $partyLabels = [
                                    'principal' => 'الموكِّل',
                                    'agent' => 'الوكيل',
                                    'seller' => 'البائع', 'buyer' => 'المشتري',
                                    'lessor' => 'المؤجر', 'lessee' => 'المستأجر',
                                ];
                                $fieldLabels = [
                                    'name' => 'الاسم',
                                    'national_id' => 'الرقم القومي',
                                    'address' => 'العنوان',
                                    'phone' => 'الهاتف',
                                    'email' => 'البريد',
                                    'nationality' => 'الجنسية',
                                    'religion' => 'الديانة',
                                    'profession' => 'المهنة',
                                    'date_of_birth' => 'تاريخ الميلاد',
                                    'commercial_register_no' => 'السجل التجاري',
                                ];
                            @endphp

                            <div class="mt-4 pt-3 border-t">
                                <div class="flex items-center justify-between mb-3">
                                    <p class="font-semibold text-gray-900 text-sm">البيانات المستخرجة بواسطة المساعد القانوني</p>
                                    <button type="button" wire:click="applyExtractedData" wire:confirm="استبدال الحقول الفارغة بالبيانات المستخرجة (لن نستبدل ما ملأته يدوياً)؟"
                                            class="text-xs px-2 py-1 bg-lexa-600 hover:bg-lexa-700 text-white rounded">
                                        تطبيق البيانات على الحقول ←
                                    </button>
                                </div>

                                {{-- Proxy metadata --}}
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-1.5 text-xs mb-4 bg-white rounded p-3 border">
                                    @if (! empty($extractedProxy['type']))
                                        <div><span class="text-gray-500">نوع التوكيل:</span> <strong>{{ $extractedProxy['type'] === 'general' ? 'عام' : 'خاص' }}</strong></div>
                                    @endif
                                    @if (! empty($extractedProxy['notary_serial']))
                                        <div><span class="text-gray-500">رقم التوثيق:</span> <strong>{{ $extractedProxy['notary_serial'] }}</strong></div>
                                    @endif
                                    @if (! empty($extractedProxy['notary_office']))
                                        <div class="sm:col-span-2"><span class="text-gray-500">مكتب التوثيق:</span> <strong>{{ $extractedProxy['notary_office'] }}</strong></div>
                                    @endif
                                    @if (! empty($extractedProxy['issue_date']))
                                        <div><span class="text-gray-500">تاريخ الإصدار:</span> <strong>{{ $extractedProxy['issue_date'] }}</strong></div>
                                    @endif
                                    @if (! empty($extractedProxy['expiry_date']))
                                        <div><span class="text-gray-500">تاريخ الانتهاء:</span> <strong>{{ $extractedProxy['expiry_date'] }}</strong></div>
                                    @endif
                                    @if (! empty($extractedProxy['scope']))
                                        <div class="sm:col-span-2 pt-1 border-t mt-1">
                                            <span class="text-gray-500">نطاق التوكيل / الصلاحيات:</span>
                                            <p class="mt-1" dir="rtl">{{ $extractedProxy['scope'] }}</p>
                                        </div>
                                    @endif
                                </div>

                                {{-- Parties (principal + agent + anything else Claude found) --}}
                                @if (! empty($extractedParties))
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4">
                                        @foreach ($extractedParties as $ns => $fields)
                                            @continue(! is_array($fields) || empty(array_filter($fields)))
                                            <div class="bg-white rounded p-3 border">
                                                <p class="text-xs font-semibold text-lexa-700 mb-2">{{ $partyLabels[$ns] ?? $ns }}</p>
                                                <dl class="space-y-1 text-xs">
                                                    @foreach ($fields as $field => $value)
                                                        @continue(empty($value))
                                                        <div class="flex">
                                                            <dt class="text-gray-500 w-28 flex-shrink-0">{{ $fieldLabels[$field] ?? $field }}:</dt>
                                                            <dd class="text-gray-900 flex-1" dir="rtl">{{ $value }}</dd>
                                                        </div>
                                                    @endforeach
                                                </dl>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="bg-amber-50 border-s-4 border-amber-400 p-2 text-xs text-amber-800 mb-3">
                                        لم يتمكن المساعد من التعرف على الأطراف بوضوح. راجع نص الوثيقة أدناه وأدخل بياناتهم يدوياً.
                                    </div>
                                @endif

                                {{-- Subject property --}}
                                @if ($extractedSubject)
                                    <div class="bg-white rounded p-3 border text-xs mb-4">
                                        <p class="text-gray-500 mb-1">موضوع التوكيل (تفاصيل):</p>
                                        <p dir="rtl">{{ $extractedSubject }}</p>
                                    </div>
                                @endif

                                {{-- Witnesses --}}
                                @if (! empty($extractedWitnesses))
                                    <div class="bg-white rounded p-3 border text-xs mb-4">
                                        <p class="text-gray-500 font-medium mb-1">الشهود:</p>
                                        <ol class="ms-5 list-decimal space-y-0.5">
                                            @foreach ($extractedWitnesses as $w)
                                                <li>
                                                    {{ $w['name'] ?? '—' }}
                                                    @if (! empty($w['national_id'])) <span class="text-gray-500">({{ $w['national_id'] }})</span> @endif
                                                </li>
                                            @endforeach
                                        </ol>
                                    </div>
                                @endif

                                {{-- Raw OCR text — collapsible, for the lawyer to verify Claude got it right --}}
                                @if ($proxy->extracted_text)
                                    <details class="bg-gray-100 rounded border text-xs">
                                        <summary class="cursor-pointer px-3 py-2 font-medium text-gray-700 hover:bg-gray-200 rounded">
                                            نص الوثيقة الكامل كما قرأه المساعد (للمراجعة) — انقر للعرض
                                        </summary>
                                        <div class="p-3 border-t max-h-96 overflow-y-auto whitespace-pre-wrap font-arabic leading-7" dir="rtl">{{ $proxy->extracted_text }}</div>
                                    </details>
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
