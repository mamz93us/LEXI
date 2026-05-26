<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-6">
        <h2 class="text-2xl font-semibold text-gray-900">مسودة آلية جديدة</h2>

        <div class="flex items-center gap-2 text-sm">
            <span class="px-2 py-1 rounded {{ $step >= 1 ? 'bg-lexa-600 text-white' : 'bg-gray-200' }}">1. القالب</span>
            <span class="text-gray-400">›</span>
            <span class="px-2 py-1 rounded {{ $step >= 2 ? 'bg-lexa-600 text-white' : 'bg-gray-200' }}">2. الأطراف والبيانات</span>
            <span class="text-gray-400">›</span>
            <span class="px-2 py-1 rounded {{ $step >= 3 ? 'bg-lexa-600 text-white' : 'bg-gray-200' }}">3. البنود</span>
            <span class="text-gray-400">›</span>
            <span class="px-2 py-1 rounded {{ $step >= 4 ? 'bg-lexa-600 text-white' : 'bg-gray-200' }}">4. التوليد</span>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            {{-- ===================== STEP 1 ===================== --}}
            @if ($step === 1)
                <h3 class="font-semibold mb-4">اختر قالباً</h3>
                @if ($this->activeTemplates->isEmpty())
                    <p class="text-sm text-gray-500">لا توجد قوالب نشطة. أضف قالباً من <a href="{{ route('templates.create') }}" wire:navigate class="text-lexa-700 hover:underline">صفحة القوالب</a> أولاً.</p>
                @else
                    <div class="space-y-2">
                        @foreach ($this->activeTemplates as $t)
                            <label class="flex items-start gap-3 p-3 border rounded-md cursor-pointer hover:bg-gray-50">
                                <input type="radio" wire:model.live="template_id" value="{{ $t->id }}" class="mt-1" />
                                <div>
                                    <div class="font-medium">{{ $t->title }}</div>
                                    <div class="text-xs text-gray-500">{{ $t->type }} · v{{ $t->currentVersion?->version_no ?? '—' }}</div>
                                    @if ($t->description)
                                        <div class="text-sm text-gray-600 mt-1">{{ $t->description }}</div>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
                @error('template_id') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700">القضية المرتبطة (اختياري)</label>
                    <select wire:model="case_id" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">— بدون قضية —</option>
                        @foreach ($this->casesList as $c)
                            <option value="{{ $c->id }}">{{ $c->case_number }} — {{ $c->title }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- ===================== STEP 2 ===================== --}}
            @if ($step === 2)
                <h3 class="font-semibold mb-1">الأطراف وبيانات العقد</h3>
                <p class="text-sm text-gray-500 mb-5">
                    اختر العملاء كأطراف للعقد — سيتم تعبئة جميع بياناتهم (الاسم، الرقم القومي، العنوان، الجنسية، الديانة، المهنة …) تلقائياً.
                </p>

                {{-- Party pickers --}}
                @if (! empty($this->detectedParties))
                    <div class="space-y-4 mb-6">
                        <h4 class="text-sm font-semibold text-gray-900 border-b pb-1">الأطراف</h4>
                        @foreach ($this->detectedParties as $ns)
                            @php
                                $label = $this->partyLabels[$ns] ?? $ns;
                                $selectedId = $parties[$ns] ?? null;
                                $selected = $selectedId ? $this->clientsList->firstWhere('id', (int) $selectedId) : null;
                            @endphp
                            <div class="border rounded-md p-3 bg-gray-50">
                                <label class="block text-sm font-medium text-gray-700 mb-1">
                                    {{ $label }} <span class="text-xs text-gray-500">(@{{ {{ $ns }}.* @})</span>
                                </label>
                                <select wire:model.live="parties.{{ $ns }}"
                                        class="w-full rounded-md border-gray-300 shadow-sm">
                                    <option value="">— اختر عميلاً —</option>
                                    @foreach ($this->clientsList as $cl)
                                        <option value="{{ $cl->id }}">
                                            {{ $cl->name_ar ?: $cl->name }}@if ($cl->national_id) — {{ $cl->national_id }} @endif
                                        </option>
                                    @endforeach
                                </select>
                                @if ($selected)
                                    <div class="mt-2 text-xs text-gray-600 space-y-0.5">
                                        @if ($selected->national_id) <div>الرقم القومي: <code>{{ $selected->national_id }}</code></div> @endif
                                        @if ($selected->type === 'company') <div class="text-amber-700">نوع العميل: شركة</div> @endif
                                        <a href="{{ route('clients.edit', $selected->id) }}" target="_blank"
                                           class="text-lexa-700 hover:underline">عرض/تعديل بيانات العميل ↗</a>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Contract metadata fields --}}
                @if (! empty($this->detectedContractMeta))
                    <div class="space-y-4 mb-6">
                        <h4 class="text-sm font-semibold text-gray-900 border-b pb-1">بيانات العقد</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($this->detectedContractMeta as $token)
                                @php $def = $this->metaFieldDefs[$token] ?? ['label_ar' => $token, 'type' => 'text']; @endphp
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        {{ $def['label_ar'] }} <span class="text-xs text-gray-500">(@{{ {{ $token }} @})</span>
                                    </label>
                                    @if (($def['source'] ?? null) === 'courts')
                                        <select wire:model="contract_meta.{{ $token }}"
                                                class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                                            <option value="">— اختر المحكمة —</option>
                                            @foreach ($this->courtsList as $crt)
                                                <option value="{{ $crt->id }}">
                                                    {{ $crt->name_ar ?: $crt->name_en }}@if ($crt->governorate) — {{ $crt->governorate }} @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    @elseif ($def['type'] === 'textarea')
                                        <textarea wire:model="contract_meta.{{ $token }}" rows="2"
                                                  class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
                                    @elseif ($def['type'] === 'date')
                                        <input wire:model="contract_meta.{{ $token }}" type="date"
                                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                                    @else
                                        <input wire:model="contract_meta.{{ $token }}" type="text"
                                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Custom (non-catalog) variables, if the template defines any --}}
                @if (! empty($this->templateCustomVariables))
                    <div class="space-y-4 mb-6">
                        <h4 class="text-sm font-semibold text-gray-900 border-b pb-1">حقول إضافية</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($this->templateCustomVariables as $var)
                                @php
                                    $name = $var['name'] ?? '';
                                    $label = $var['label_ar'] ?? ($var['label_en'] ?? $name);
                                    $type = $var['type'] ?? 'text';
                                    $required = $var['required'] ?? false;
                                @endphp
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">
                                        {{ $label }} @if ($required) <span class="text-red-500">*</span> @endif
                                    </label>
                                    @if ($type === 'textarea')
                                        <textarea wire:model="filled.{{ $name }}" rows="2"
                                                  class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
                                    @else
                                        <input wire:model="filled.{{ $name }}" type="{{ $type === 'number' ? 'number' : 'text' }}"
                                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if (empty($this->detectedParties) && empty($this->detectedContractMeta) && empty($this->templateCustomVariables))
                    <div class="bg-amber-50 border-s-4 border-amber-400 p-3 mb-4">
                        <p class="text-sm text-amber-800">
                            القالب لا يحتوي على أي متغيرات قابلة للتعبئة. اضغط «التالي» للمتابعة وسيُولّد العقد كما هو.
                        </p>
                        <p class="text-xs text-amber-700 mt-1">
                            لإضافة متغيرات (مثل البائع، المشتري، مكان العقد …) ارجع إلى
                            <a href="{{ route('templates.edit', $this->template) }}" wire:navigate class="text-lexa-700 hover:underline">صفحة القالب</a>
                            واستخدم قائمة المتغيرات الجاهزة.
                        </p>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700">تعليمات إضافية للمساعد (اختياري)</label>
                    <textarea wire:model="user_intent" rows="3" dir="rtl"
                              placeholder="مثال: استخدم نبرة رسمية، أضف بند التحكيم في القاهرة، …"
                              class="mt-1 w-full rounded-md border-gray-300 shadow-sm"></textarea>
                </div>
            @endif

            {{-- ===================== STEP 3 ===================== --}}
            @if ($step === 3)
                <h3 class="font-semibold mb-4">اختر البنود التي ستُدرج حرفياً</h3>
                @if ($this->approvedClauses->isEmpty())
                    <p class="text-sm text-gray-500">
                        لا توجد بنود معتمدة في المكتبة بعد. تابع بدون بنود (المسودة ستعتمد على القالب فقط)،
                        أو ارجع لـ <a href="{{ route('clauses.index') }}" wire:navigate class="text-lexa-700 hover:underline">مكتبة البنود</a> لإضافة بنود معتمدة من شريك.
                    </p>
                @else
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        @foreach ($this->approvedClauses as $c)
                            <label class="flex items-start gap-3 p-3 border rounded-md cursor-pointer hover:bg-gray-50">
                                <input type="checkbox" wire:model.live="clause_ids" value="{{ $c->id }}" class="mt-1" />
                                <div class="flex-1">
                                    <div class="flex justify-between">
                                        <span class="font-medium">{{ $c->title }}</span>
                                        <span class="text-xs text-gray-500">{{ $c->topic }}</span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2">{{ \Illuminate\Support\Str::limit($c->currentVersion?->body ?? '', 180) }}</p>
                                </div>
                            </label>
                        @endforeach
                    </div>
                @endif
            @endif

            {{-- ===================== STEP 4 ===================== --}}
            @if ($step === 4)
                <h3 class="font-semibold mb-4">توليد المسودة</h3>
                @if ($output === null && $error === null)
                    <div class="space-y-3">
                        <p class="text-sm text-gray-600">سيتم استبدال جميع المتغيرات في القالب ببيانات الأطراف، ثم سيُرسل القالب لـ Claude مع البنود المعتمدة وسياق الاسترجاع من أرشيف العقود.</p>
                        <button wire:click="generate" wire:loading.attr="disabled"
                                class="px-6 py-3 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md disabled:opacity-50">
                            <span wire:loading.remove wire:target="generate">توليد المسودة</span>
                            <span wire:loading wire:target="generate">جاري التوليد...</span>
                        </button>
                    </div>
                @endif

                @if ($error)
                    @php
                        $isCohereErr = str_contains($error, 'Cohere') || str_contains(strtolower($error), 'cohere');
                        $isAnthropicErr = str_contains($error, 'Anthropic') || str_contains($error, 'anthropic');
                        $isKeyErr = str_contains($error, 'API key') || str_contains($error, '401') || str_contains($error, 'unauthorized');
                    @endphp
                    <div class="bg-red-50 border-s-4 border-red-400 p-4 mb-4">
                        <p class="text-sm font-medium text-red-800 mb-1">فشل التوليد</p>
                        <p class="text-xs text-red-700 font-mono whitespace-pre-wrap">{{ $error }}</p>

                        @if ($isCohereErr && $isKeyErr)
                            <p class="text-sm text-red-700 mt-2">
                                مفتاح خدمة التضمين (Cohere) مرفوض. افتح
                                <a href="{{ route('settings.index') }}" class="text-lexa-700 hover:underline">الإعدادات → Embeddings</a>
                                وأعد إدخال المفتاح، أو غيّر «المزود» إلى «معطّل (Null)» للاستمرار بدون استرجاع دلالي.
                            </p>
                        @elseif ($isAnthropicErr && $isKeyErr)
                            <p class="text-sm text-red-700 mt-2">
                                مفتاح Anthropic مرفوض أو غير مضبوط. افتح
                                <a href="{{ route('settings.index') }}" class="text-lexa-700 hover:underline">الإعدادات → الذكاء الاصطناعي</a>
                                وأدخل مفتاحاً صحيحاً ثم اختبر الاتصال.
                            </p>
                        @elseif ($isKeyErr)
                            <p class="text-sm text-red-700 mt-2">
                                مفتاح API مرفوض. راجع
                                <a href="{{ route('settings.index') }}" class="text-lexa-700 hover:underline">الإعدادات</a>
                                وتأكد من صحة مفاتيح Anthropic و Cohere.
                            </p>
                        @endif
                    </div>
                @endif

                @if ($output)
                    <div class="bg-amber-50 border-s-4 border-amber-400 p-3 mb-4">
                        <p class="text-sm font-medium text-amber-800">مسودة آلية — قيد المراجعة</p>
                        <p class="text-xs text-amber-700 mt-1">يجب أن يراجعها شريك قبل اعتمادها. لا تُرسل قبل الاعتماد.</p>
                    </div>
                    <pre class="text-sm whitespace-pre-wrap font-arabic bg-gray-50 p-4 rounded-md max-h-[600px] overflow-y-auto" dir="rtl">{{ $output }}</pre>
                    <div class="mt-4 flex justify-end gap-3">
                        <a href="{{ route('ai-drafter.index') }}" wire:navigate
                           class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">عرض كل المسودات</a>
                    </div>
                @endif
            @endif
        </div>

        <div class="flex justify-between">
            <button wire:click="back" @disabled($step === 1)
                    class="px-4 py-2 text-sm bg-gray-100 hover:bg-gray-200 rounded-md disabled:opacity-30">السابق</button>
            @if ($step < 4)
                <button wire:click="next"
                        class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">التالي</button>
            @endif
        </div>
    </div>
</div>
