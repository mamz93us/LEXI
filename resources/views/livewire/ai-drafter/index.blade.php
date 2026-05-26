<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">صياغة العقود الآلية</h2>
                <p class="text-sm text-gray-500 mt-1">مسودات يولّدها المساعد بناءً على قوالب وبنود مكتبك. كل مسودة تظهر بعلامة «مسودة آلية — قيد المراجعة» حتى يعتمدها شريك.</p>
            </div>
            <a href="{{ route('ai-drafter.wizard') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md transition">
                مسودة جديدة
            </a>
        </div>

        @if (session('error'))
            <div class="mb-4 bg-red-50 border-s-4 border-red-400 p-3 text-sm text-red-700">{{ session('error') }}</div>
        @endif

        <div class="space-y-3">
            @forelse ($this->generations as $g)
                <a href="{{ route('ai-drafter.show', $g) }}" wire:navigate
                   class="block bg-white shadow-sm rounded-lg p-4 hover:shadow-md transition">
                    <div class="flex justify-between items-start mb-2">
                        <div>
                            <span class="text-xs px-2 py-1 rounded
                                {{ $g->status === 'approved' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $g->status === 'rejected' ? 'bg-red-100 text-red-800' : '' }}
                                {{ in_array($g->status, ['draft', 'reviewed']) ? 'bg-amber-100 text-amber-800' : '' }}">
                                {{ $g->status === 'draft' ? 'مسودة آلية — قيد المراجعة' : $g->status }}
                            </span>
                            @if ($g->revision_kind && $g->revision_kind !== 'initial')
                                <span class="text-xs px-2 py-1 rounded bg-lexa-50 text-lexa-700 ms-1">
                                    {{ $g->revision_kind === 'ai_refine' ? 'تعديل آلي' : 'تعديل يدوي' }}
                                </span>
                            @endif
                            <span class="text-xs text-gray-500 ms-2">{{ $g->created_at?->format('Y-m-d H:i') }} · {{ $g->model }}</span>
                        </div>
                        <span class="text-xs text-lexa-700">فتح ←</span>
                    </div>
                    <pre class="text-sm whitespace-pre-wrap font-arabic" dir="rtl">{{ \Illuminate\Support\Str::limit($g->output ?? '', 500) }}</pre>
                </a>
            @empty
                <div class="bg-white shadow-sm rounded-lg p-12 text-center text-gray-500">
                    لا توجد مسودات بعد. ابدأ بإضافة قالب وبنود معتمدة، ثم أنشئ مسودة جديدة.
                </div>
            @endforelse
        </div>
    </div>
</div>
