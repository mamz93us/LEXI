<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-6">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">صياغة سريعة — بدون قالب</h2>
            <p class="text-sm text-gray-500 mt-1">
                اختر نوع الوثيقة واكتب وصف ما تريد. سيحدد المساعد القانوني البيانات المطلوبة، ثم تملأها لتُصاغ المسودة النهائية.
            </p>
        </div>

        <div class="flex items-center gap-2 text-sm">
            <span class="px-2 py-1 rounded {{ $step >= 1 ? 'bg-lexa-600 text-white' : 'bg-gray-200' }}">1. النوع والوصف</span>
            <span class="text-gray-400">›</span>
            <span class="px-2 py-1 rounded {{ $step >= 2 ? 'bg-lexa-600 text-white' : 'bg-gray-200' }}">2. البيانات والأطراف</span>
        </div>

        @if ($error)
            <div class="bg-red-50 border-s-4 border-red-500 p-3 text-sm text-red-800">{{ $error }}</div>
        @endif
        @if ($info)
            <div class="bg-green-50 border-s-4 border-green-500 p-3 text-sm text-green-800">{{ $info }}</div>
        @endif

        {{-- ==================== STEP 1 ==================== --}}
        @if ($step === 1)
            <div class="bg-white shadow-sm rounded-lg p-6 space-y-5">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">نوع الوثيقة</label>
                    <select wire:model.live="doc_type"
                            class="w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">— اختر نوع الوثيقة —</option>
                        @foreach ($this->docTypes as $category => $types)
                            <optgroup label="{{ $category }}">
                                @foreach ($types as $key => $meta)
                                    <option value="{{ $key }}">{{ $meta['label_ar'] }} ({{ $meta['label_en'] }})</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        وصف الطلب
                    </label>
                    <p class="text-xs text-gray-500 mb-2">
                        اشرح بالتفصيل: الموضوع، الأطراف بشكل عام، القيمة، المدة، الشروط الخاصة، أي بنود يجب تضمينها.
                    </p>
                    <textarea wire:model="description" rows="6" dir="rtl"
                              placeholder="{{ $this->docTypeMeta['description_placeholder'] ?? 'مثال: بيع شقة في القاهرة بقيمة 3 مليون جنيه…' }}"
                              class="w-full rounded-md border-gray-300 shadow-sm font-arabic leading-7"></textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">القضية المرتبطة (اختياري)</label>
                    <select wire:model="case_id" class="w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">— بدون قضية —</option>
                        @foreach ($this->casesList as $c)
                            <option value="{{ $c->id }}">{{ $c->case_number }} — {{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($this->linkableProxies->isNotEmpty())
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            استخدم بيانات توكيل قائم (اختياري)
                        </label>
                        <p class="text-xs text-gray-500 mb-2">
                            عند الاختيار، تُملأ بيانات الموكِّل/الوكيل + رقم التوثيق + النطاق تلقائياً من التوكيل الذي رفعته سابقاً.
                        </p>
                        <select wire:model.live="linked_proxy_id" class="w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">— بدون توكيل مرتبط —</option>
                            @foreach ($this->linkableProxies as $p)
                                <option value="{{ $p->id }}" @if ($p->extraction_status !== 'extracted') disabled @endif>
                                    {{ $p->client?->name_ar ?: $p->client?->name }} —
                                    {{ $p->notary_serial ?: 'بدون رقم توثيق' }}
                                    @if ($p->extraction_status !== 'extracted')
                                        ({{ ['pending' => 'ينتظر…', 'extracting' => 'جاري…', 'failed' => 'فشل'][$p->extraction_status] ?? '—' }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex justify-end gap-3 pt-4 border-t">
                    <a href="{{ route('ai-drafter.index') }}" wire:navigate
                       class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">إلغاء</a>
                    <button wire:click="startDiscovery" wire:loading.attr="disabled"
                            class="px-6 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md disabled:opacity-50">
                        <span wire:loading.remove wire:target="startDiscovery">المتابعة — حدد المساعد البيانات المطلوبة</span>
                        <span wire:loading wire:target="startDiscovery">جاري التحليل…</span>
                    </button>
                </div>
            </div>
        @endif

        {{-- ==================== STEP 2 ==================== --}}
        @if ($step === 2)
            @if (! empty($this->discovery['lawyer_warnings']))
                <div class="bg-amber-50 border-s-4 border-amber-400 p-4 rounded">
                    <p class="text-sm font-semibold text-amber-900 mb-2">تنبيهات قانونية يجب مراجعتها قبل الاعتماد:</p>
                    <ul class="text-sm text-amber-800 space-y-1 ms-5 list-disc">
                        @foreach ($this->discovery['lawyer_warnings'] as $w)
                            <li>{{ $w }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Parties --}}
            @if (! empty($this->partiesToFill))
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">الأطراف</h3>
                    <p class="text-xs text-gray-500 mb-4">
                        اختر العميل لكل طرف — ستُملأ بياناته الكاملة (اسم، رقم قومي، عنوان، جنسية، ديانة، مهنة) تلقائياً.
                        للوكيل في التوكيلات يمكنك اختياره من محاميي المكتب بدلاً من العملاء.
                    </p>
                    <div class="space-y-3">
                        @foreach ($this->partiesToFill as $p)
                            @php
                                $ns = $p['namespace'];
                                $kind = $parties_kind[$ns] ?? 'client';
                                $selectedId = $parties[$ns] ?? null;
                                $selectedClient = ($kind === 'client' && $selectedId)
                                    ? $this->clientsList->firstWhere('id', (int) $selectedId)
                                    : null;
                                $selectedLawyer = ($kind === 'lawyer' && $selectedId)
                                    ? $this->lawyersList->firstWhere('id', (int) $selectedId)
                                    : null;
                            @endphp
                            <div class="border rounded-md p-3 bg-gray-50">
                                <div class="flex items-baseline justify-between mb-1">
                                    <label class="block text-sm font-medium text-gray-700">{{ $p['label_ar'] }}</label>
                                    <div class="text-xs space-x-2 space-x-reverse">
                                        <label class="inline-flex items-center gap-1 cursor-pointer">
                                            <input type="radio" wire:model.live="parties_kind.{{ $ns }}" value="client" class="border-gray-300" />
                                            <span>من العملاء</span>
                                        </label>
                                        <label class="inline-flex items-center gap-1 cursor-pointer">
                                            <input type="radio" wire:model.live="parties_kind.{{ $ns }}" value="lawyer" class="border-gray-300" />
                                            <span>من محاميي المكتب</span>
                                        </label>
                                    </div>
                                </div>

                                @if ($kind === 'lawyer')
                                    <select wire:model.live="parties.{{ $ns }}"
                                            class="w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="">— اختر محامياً —</option>
                                        @foreach ($this->lawyersList as $u)
                                            <option value="{{ $u->id }}">
                                                {{ $u->name }}@if ($u->email) — {{ $u->email }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($selectedLawyer)
                                        <div class="mt-2 text-xs text-gray-600 space-y-0.5">
                                            <div>محامي بالمكتب · {{ $selectedLawyer->name }}</div>
                                            @if ($selectedLawyer->phone) <div>الهاتف: <code>{{ $selectedLawyer->phone }}</code></div> @endif
                                            <div class="text-amber-700">ملاحظة: الرقم القومي والعنوان غير متوفرين في ملف المحامي — سيُترك مكانهما فارغاً ليكمله المراجع.</div>
                                        </div>
                                    @endif
                                @else
                                    <select wire:model.live="parties.{{ $ns }}"
                                            class="w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="">— اختر عميلاً —</option>
                                        @foreach ($this->clientsList as $cl)
                                            <option value="{{ $cl->id }}">
                                                {{ $cl->name_ar ?: $cl->name }}@if ($cl->national_id) — {{ $cl->national_id }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    @if ($selectedClient)
                                        <div class="mt-2 text-xs text-gray-600 space-y-0.5">
                                            @if ($selectedClient->national_id) <div>الرقم القومي: <code>{{ $selectedClient->national_id }}</code></div> @endif
                                            @if ($selectedClient->type === 'company') <div class="text-amber-700">نوع العميل: شركة</div> @endif
                                            <a href="{{ route('clients.edit', $selectedClient->id) }}" target="_blank"
                                               class="text-lexa-700 hover:underline">عرض/تعديل بيانات العميل ↗</a>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Contract metadata (place, date, value, court...) --}}
            @if (! empty($this->contractMetaToFill))
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">بيانات العقد</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($this->contractMetaToFill as $def)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    {{ $def['label_ar'] }}
                                </label>
                                @if (($def['source'] ?? null) === 'courts')
                                    <select wire:model="contract_meta.{{ $def['token'] }}"
                                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                                        <option value="">— اختر المحكمة —</option>
                                        @foreach ($this->courtsList as $crt)
                                            <option value="{{ $crt->id }}">
                                                {{ $crt->name_ar ?: $crt->name_en }}@if ($crt->governorate) — {{ $crt->governorate }} @endif
                                            </option>
                                        @endforeach
                                    </select>
                                @elseif ($def['type'] === 'textarea')
                                    <textarea wire:model="contract_meta.{{ $def['token'] }}" rows="2"
                                              class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
                                @elseif ($def['type'] === 'date')
                                    <input wire:model="contract_meta.{{ $def['token'] }}" type="date"
                                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                                @else
                                    <input wire:model="contract_meta.{{ $def['token'] }}" type="text"
                                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- AI-discovered extra fields --}}
            @if (! empty($this->discovery['fields'] ?? []))
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">حقول خاصة بهذه الوثيقة</h3>
                    <p class="text-xs text-gray-500 mb-4">حدّدها المساعد بناءً على نوع الوثيقة ووصفك.</p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($this->discovery['fields'] as $f)
                            <div>
                                <label class="block text-sm font-medium text-gray-700">
                                    {{ $f['label_ar'] }}
                                    @if ($f['required']) <span class="text-red-500">*</span> @endif
                                    <span class="text-xs text-gray-400 font-mono ms-1">{{ $f['key'] }}</span>
                                </label>
                                @if ($f['type'] === 'textarea')
                                    <textarea wire:model="extra_fields.{{ $f['key'] }}" rows="2"
                                              class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
                                @elseif ($f['type'] === 'date')
                                    <input wire:model="extra_fields.{{ $f['key'] }}" type="date"
                                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                                @elseif ($f['type'] === 'number')
                                    <input wire:model="extra_fields.{{ $f['key'] }}" type="number"
                                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                                @else
                                    <input wire:model="extra_fields.{{ $f['key'] }}" type="text"
                                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Optional approved clauses --}}
            @if ($this->approvedClauses->isNotEmpty())
                <div class="bg-white shadow-sm rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-1">بنود معتمدة (اختياري)</h3>
                    <p class="text-xs text-gray-500 mb-3">اختر بنوداً من مكتبة المكتب لإدراجها حرفياً.</p>
                    <div class="space-y-2 max-h-72 overflow-y-auto">
                        @foreach ($this->approvedClauses as $c)
                            <label class="flex items-start gap-3 p-2 border rounded-md cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" wire:model="clause_ids" value="{{ $c->id }}" class="mt-1" />
                                <div class="flex-1 text-sm">
                                    <div class="flex justify-between">
                                        <span class="font-medium">{{ $c->title }}</span>
                                        <span class="text-xs text-gray-500">{{ $c->topic }}</span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2">
                                        {{ \Illuminate\Support\Str::limit($c->currentVersion?->body ?? '', 180) }}
                                    </p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="flex justify-between bg-white shadow-sm rounded-lg p-4">
                <button wire:click="back"
                        class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-md">السابق</button>
                <button wire:click="generate" wire:loading.attr="disabled"
                        class="px-6 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md disabled:opacity-50">
                    <span wire:loading.remove wire:target="generate">صياغة المسودة بالذكاء الاصطناعي</span>
                    <span wire:loading wire:target="generate">جاري الإرسال للقائمة…</span>
                </button>
            </div>
        @endif
    </div>
</div>
