<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-3xl mx-auto space-y-6">
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">الإعدادات</h2>
            <p class="text-sm text-gray-500 mt-1">إعدادات مخصصة لمكتب {{ tenant('name') }}. القيم الحساسة تُخزَّن مشفّرة.</p>
        </div>

        @if (session('saved'))
            <div class="bg-green-50 border-s-4 border-green-500 p-3 text-sm text-green-800">{{ session('saved') }}</div>
        @endif

        <form wire:submit="save" class="space-y-6">
            {{-- ===== AI ===== --}}
            <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">الذكاء الاصطناعي (Anthropic Claude)</h3>
                        <p class="text-xs text-gray-500 mt-1">يُستعمل لتوليد مسودات العقود في «صياغة آلية».</p>
                    </div>
                    @if ($anthropic_key_set)
                        <button type="button" wire:click="testAnthropic" wire:loading.attr="disabled"
                                class="text-sm px-3 py-1.5 bg-gray-100 hover:bg-gray-200 rounded-md disabled:opacity-50">
                            <span wire:loading.remove wire:target="testAnthropic">اختبر الاتصال</span>
                            <span wire:loading wire:target="testAnthropic">جاري الاختبار…</span>
                        </button>
                    @endif
                </div>

                @if ($test_result)
                    <div class="bg-green-50 border-s-4 border-green-500 p-3 text-sm text-green-800">{{ $test_result }}</div>
                @endif
                @if ($test_error)
                    <div class="bg-red-50 border-s-4 border-red-500 p-3 text-sm text-red-800">
                        <p class="font-medium">فشل الاتصال:</p>
                        <p class="mt-1 font-mono text-xs whitespace-pre-wrap">{{ $test_error }}</p>
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700">مفتاح API</label>
                    @if ($anthropic_key_set && $anthropic_new_key === '')
                        <div class="mt-1 flex items-center gap-3">
                            <span class="text-sm text-gray-600">●●●●●●●● محفوظ ومشفّر</span>
                            <input wire:model.live="anthropic_new_key" type="password" placeholder="اكتب مفتاحاً جديداً للاستبدال"
                                   class="flex-1 rounded-md border-gray-300 shadow-sm" />
                            <button type="button" wire:click="clearAnthropicKey" wire:confirm="حذف المفتاح يوقف عمل صياغة العقود الآلية. متابعة؟"
                                    class="text-sm text-red-600 hover:text-red-800">حذف</button>
                        </div>
                    @else
                        <input wire:model="anthropic_new_key" type="password"
                               placeholder="sk-ant-..."
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                        <p class="mt-1 text-xs text-gray-500">احصل عليه من <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-lexa-700 hover:underline">console.anthropic.com</a>. استخدم مفتاحاً ضمن اتفاقية «zero-retention» للحفاظ على السرية.</p>
                    @endif
                    @error('anthropic_new_key') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">النموذج</label>
                        <input wire:model="anthropic_model" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">حد الرموز (Max tokens)</label>
                        <input wire:model="anthropic_max_tokens" type="number" min="256" max="200000"
                               class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                        <p class="mt-1 text-xs text-gray-500">
                            توكيلات: 2048 — عقود قياسية: 4096–8192 — عقود طويلة (بيع، شراكة): 16384+
                        </p>
                    </div>
                    <div class="flex items-end">
                        <label class="inline-flex items-center gap-2 text-sm">
                            <input wire:model="anthropic_zero_retention" type="checkbox" class="rounded border-gray-300" />
                            وضع الاحتفاظ الصفري (zero-retention)
                        </label>
                    </div>
                </div>
            </div>

            {{-- ===== Embeddings ===== --}}
            <div class="bg-white shadow-sm rounded-lg p-6 space-y-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">خدمة التضمين (Embeddings)</h3>
                    <p class="text-xs text-gray-500 mt-1">تُستعمل لفهرسة عقود المكتب وبحث الاسترجاع الدلالي عند توليد مسودات جديدة.</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">المزود</label>
                        <select wire:model.live="embeddings_driver" class="mt-1 w-full rounded-md border-gray-300 shadow-sm">
                            <option value="null">معطّل (Null)</option>
                            <option value="cohere">Cohere</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">النموذج</label>
                        <input wire:model="embeddings_model" type="text" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">الأبعاد (Dimension)</label>
                        <input wire:model="embeddings_dimension" type="number" class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                        <p class="mt-1 text-xs text-amber-700">يجب أن يطابق ما يصدره النموذج (Cohere v3 = 1024).</p>
                    </div>
                </div>

                @if ($embeddings_driver !== 'null')
                    <div>
                        <label class="block text-sm font-medium text-gray-700">مفتاح API</label>
                        @if ($embeddings_key_set && $embeddings_new_key === '')
                            <div class="mt-1 flex items-center gap-3">
                                <span class="text-sm text-gray-600">●●●●●●●● محفوظ ومشفّر</span>
                                <input wire:model.live="embeddings_new_key" type="password" placeholder="اكتب مفتاحاً جديداً للاستبدال"
                                       class="flex-1 rounded-md border-gray-300 shadow-sm" />
                                <button type="button" wire:click="clearEmbeddingsKey"
                                        class="text-sm text-red-600 hover:text-red-800">حذف</button>
                            </div>
                        @else
                            <input wire:model="embeddings_new_key" type="password"
                                   class="mt-1 w-full rounded-md border-gray-300 shadow-sm" />
                        @endif
                        @error('embeddings_new_key') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

            <div class="flex justify-end">
                <button type="submit"
                        class="px-6 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">
                    حفظ التغييرات
                </button>
            </div>
        </form>
    </div>
</div>
