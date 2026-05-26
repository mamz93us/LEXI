<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-6">

        {{-- Header --}}
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">مسودة آلية</h2>
                <p class="text-sm text-gray-500 mt-1">
                    إصدار رقم {{ $this->chain->search(fn ($g) => $g->id === $generation->id) + 1 }}
                    من {{ $this->chain->count() }}
                    · أُنشئت {{ $generation->created_at?->diffForHumans() }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs px-3 py-1 rounded
                    {{ $generation->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $generation->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                    {{ in_array($generation->status, ['draft', 'reviewed']) ? 'bg-amber-100 text-amber-800' : '' }}">
                    {{ ['draft' => 'مسودة آلية — قيد المراجعة', 'reviewed' => 'تمت المراجعة', 'approved' => 'معتمد', 'rejected' => 'مرفوض'][$generation->status] ?? $generation->status }}
                </span>
                <a href="{{ route('ai-drafter.index') }}" wire:navigate
                   class="text-sm text-lexa-700 hover:text-lexa-900">← كل المسودات</a>
            </div>
        </div>

        @if ($info)
            <div class="bg-green-50 border-s-4 border-green-500 p-3 text-sm text-green-800">{{ $info }}</div>
        @endif
        @if ($error)
            <div class="bg-red-50 border-s-4 border-red-500 p-3 text-sm text-red-800">{{ $error }}</div>
        @endif

        {{-- Current output --}}
        <div class="bg-white shadow-sm rounded-lg p-6">
            @if ($generation->status === 'draft')
                <div class="bg-amber-50 border-s-4 border-amber-400 p-3 mb-4">
                    <p class="text-sm font-medium text-amber-800">مسودة آلية — قيد المراجعة</p>
                    <p class="text-xs text-amber-700 mt-1">يجب اعتمادها من شريك قبل الإرسال أو التوثيق.</p>
                </div>
            @endif

            @if (! $show_manual_editor)
                <pre class="text-sm whitespace-pre-wrap font-arabic leading-7 bg-gray-50 p-4 rounded-md max-h-[600px] overflow-y-auto" dir="rtl">{{ $generation->output }}</pre>
            @else
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">حرر النص يدوياً ثم احفظ كإصدار جديد</label>
                    <textarea wire:model="manual_edit" rows="24" dir="rtl"
                              class="w-full rounded-md border-gray-300 shadow-sm font-arabic leading-7"></textarea>
                    <div class="flex justify-end gap-2">
                        <button wire:click="$set('show_manual_editor', false)"
                                class="px-3 py-1.5 text-sm text-gray-700 hover:text-gray-900">إلغاء</button>
                        <button wire:click="saveManualEdit"
                                class="px-4 py-1.5 bg-lexa-600 hover:bg-lexa-700 text-white text-sm rounded-md">
                            حفظ كإصدار جديد
                        </button>
                    </div>
                </div>
            @endif
        </div>

        {{-- Action bar --}}
        <div class="bg-white shadow-sm rounded-lg p-4 flex flex-wrap items-center gap-3">
            @unless ($show_manual_editor)
                <button wire:click="$set('show_manual_editor', true)"
                        class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 rounded-md">
                    ✏️ تعديل يدوي
                </button>
            @endunless

            @if ($generation->status === 'draft')
                <button wire:click="approve" wire:confirm="اعتماد هذه المسودة كصياغة نهائية؟"
                        class="px-3 py-1.5 text-sm bg-green-600 hover:bg-green-700 text-white rounded-md">
                    ✓ اعتماد
                </button>
                <button wire:click="reject"
                        class="px-3 py-1.5 text-sm bg-gray-100 hover:bg-gray-200 rounded-md">
                    ✗ رفض
                </button>
            @endif

            <span class="ms-auto text-xs text-gray-500">
                النموذج: <code>{{ $generation->model }}</code>
            </span>
        </div>

        {{-- AI refinement --}}
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">طلب تعديل من الذكاء الاصطناعي</h3>
            <p class="text-sm text-gray-500 mb-4">
                اكتب ما تريد تغييره (مثل: «اجعل البند الأول أكثر وضوحاً»، أو «أضف بند تحكيم في القاهرة»، أو «استبدل الاسم بمحمد علي»). Claude سيعيد كامل المسودة بالتعديل المطلوب كإصدار جديد.
            </p>
            <textarea wire:model="refinement_instruction" rows="4" dir="rtl"
                      placeholder="مثال: استبدل قيمة العقد بـ 5,000,000 جنيه، وأضف بنداً يلزم البائع بتسليم المخططات الهندسية."
                      class="w-full rounded-md border-gray-300 shadow-sm font-arabic"></textarea>
            <div class="flex justify-end mt-3">
                <button wire:click="refineWithAi" wire:loading.attr="disabled"
                        class="px-5 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md disabled:opacity-50">
                    <span wire:loading.remove wire:target="refineWithAi">إرسال للذكاء الاصطناعي</span>
                    <span wire:loading wire:target="refineWithAi">جاري التعديل…</span>
                </button>
            </div>
        </div>

        {{-- Version chain --}}
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">سلسلة الإصدارات</h3>
            <ol class="space-y-3">
                @foreach ($this->chain as $i => $rev)
                    @php
                        $kindLabel = ['initial' => 'الإصدار الأول', 'ai_refine' => 'تعديل آلي', 'manual_edit' => 'تعديل يدوي'][$rev->revision_kind] ?? $rev->revision_kind;
                        $isCurrent = $rev->id === $generation->id;
                    @endphp
                    <li class="border-s-4 ps-4 py-2 {{ $isCurrent ? 'border-lexa-500 bg-lexa-50' : 'border-gray-200' }}">
                        <div class="flex justify-between items-start gap-3">
                            <div class="text-sm">
                                <span class="font-semibold">#{{ $i + 1 }} · {{ $kindLabel }}</span>
                                <span class="text-xs text-gray-500 ms-2">{{ $rev->created_at?->format('Y-m-d H:i') }}</span>
                                @if ($rev->user_instruction)
                                    <p class="text-xs text-gray-600 mt-1 line-clamp-2" dir="rtl">{{ $rev->user_instruction }}</p>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="text-xs px-2 py-0.5 rounded
                                    {{ $rev->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $rev->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ in_array($rev->status, ['draft', 'reviewed']) ? 'bg-amber-100 text-amber-800' : '' }}">
                                    {{ $rev->status }}
                                </span>
                                @unless ($isCurrent)
                                    <a href="{{ route('ai-drafter.show', $rev) }}" wire:navigate
                                       class="text-xs text-lexa-700 hover:text-lexa-900">عرض</a>
                                @endunless
                            </div>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </div>
</div>
