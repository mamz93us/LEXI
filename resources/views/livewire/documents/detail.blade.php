<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-6">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">{{ $document->title }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $document->type }} · v{{ $document->currentVersion?->version_no ?? '—' }} · {{ $document->format }}</p>
                </div>
                <a href="{{ route('documents.edit', $document) }}" wire:navigate
                   class="text-sm text-lexa-700 hover:text-lexa-900">{{ __('Edit') }}</a>
            </div>

            {{-- RAG ingestion status --}}
            @if ($document->ingestion_status)
                @php
                    $ingest = [
                        'pending' => ['في انتظار الفهرسة…', 'bg-blue-100 text-blue-800'],
                        'ingesting' => ['جاري قراءة وفهرسة الوثيقة…', 'bg-blue-100 text-blue-800 animate-pulse'],
                        'ingested' => ['تمت الفهرسة للبحث الذكي ✓ ('.$document->embedding_count.' مقطع)', 'bg-green-100 text-green-800'],
                        'skipped' => ['لم تُفهرس', 'bg-amber-100 text-amber-800'],
                        'failed' => ['فشلت الفهرسة', 'bg-red-100 text-red-800'],
                    ][$document->ingestion_status] ?? null;
                @endphp
                @if ($ingest)
                    <div class="mt-3 flex items-center gap-2 flex-wrap"
                         @if (in_array($document->ingestion_status, ['pending', 'ingesting'])) wire:poll.3s @endif>
                        <span class="text-xs px-2 py-1 rounded {{ $ingest[1] }}">{{ $ingest[0] }}</span>
                        @if ($document->ingestion_note)
                            <span class="text-xs text-gray-500 break-all">{{ $document->ingestion_note }}</span>
                        @endif
                        @unless (in_array($document->ingestion_status, ['pending', 'ingesting']))
                            <button wire:click="reindex"
                                    wire:confirm="إعادة فهرسة هذا المستند؟"
                                    class="ms-auto text-xs px-2 py-1 bg-gray-100 hover:bg-gray-200 rounded">
                                إعادة فهرسة
                            </button>
                        @endunless
                    </div>
                    <p class="text-xs text-gray-400 mt-1">الوثائق المفهرسة تُستخدم كمرجع أسلوبي عند توليد مسودات جديدة (الاسترجاع الدلالي).</p>
                @endif
            @endif
        </div>

        {{-- ===== Indexed data: chunks + extracted text ===== --}}
        @if (in_array($document->ingestion_status, ['ingested', 'skipped'], true))
            <div class="bg-white shadow-sm rounded-lg p-6">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-lg font-semibold text-gray-900">البيانات المفهرسة</h3>
                    <span class="text-xs text-gray-500">{{ $this->chunks->count() }} مقطع · {{ mb_strlen($document->ocr_text ?? '') }} حرف من النص المستخرج</span>
                </div>

                {{-- Chunks list --}}
                @if ($this->chunks->isNotEmpty())
                    <div class="space-y-2 max-h-[600px] overflow-y-auto pe-1">
                        @foreach ($this->chunks as $chunk)
                            @php
                                $meta = is_array($chunk->metadata) ? $chunk->metadata : [];
                                $kindLabel = [
                                    'preamble' => 'ديباجة',
                                    'article' => 'بند/مادة',
                                    'signature' => 'توقيع',
                                ][$meta['kind'] ?? ''] ?? ($meta['kind'] ?? '—');
                                $kindColor = [
                                    'preamble' => 'bg-blue-50 text-blue-700 border-blue-200',
                                    'article' => 'bg-lexa-50 text-lexa-700 border-lexa-200',
                                    'signature' => 'bg-gray-100 text-gray-700 border-gray-300',
                                ][$meta['kind'] ?? ''] ?? 'bg-gray-50 text-gray-600 border-gray-200';
                            @endphp
                            <details class="border rounded-md {{ $kindColor }}">
                                <summary class="cursor-pointer px-3 py-2 text-sm flex items-center gap-2 flex-wrap">
                                    <span class="text-xs px-2 py-0.5 rounded bg-white border">#{{ $chunk->chunk_index + 1 }}</span>
                                    <span class="text-xs px-2 py-0.5 rounded bg-white border">{{ $kindLabel }}</span>
                                    @if (! empty($meta['heading']))
                                        <span class="text-xs text-gray-600">{{ $meta['heading'] }}</span>
                                    @endif
                                    @if (! empty($meta['article_no']))
                                        <span class="text-xs text-gray-600">رقم {{ $meta['article_no'] }}</span>
                                    @endif
                                    <span class="text-xs text-gray-400 ms-auto">{{ mb_strlen($chunk->chunk_text) }} حرف</span>
                                </summary>
                                <div class="px-3 pb-3 pt-1 text-sm font-arabic leading-7 bg-white border-t {{ str_starts_with($kindColor, 'bg-gray') ? 'border-gray-300' : ($meta['kind'] === 'preamble' ? 'border-blue-200' : 'border-lexa-200') }}" dir="rtl">
                                    {{ $chunk->chunk_text }}
                                </div>
                            </details>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-gray-500">لا توجد مقاطع مفهرسة لهذا المستند.</p>
                @endif

                {{-- Full OCR text (collapsible) --}}
                @if ($document->ocr_text)
                    <details class="mt-4 bg-gray-50 border rounded-md">
                        <summary class="cursor-pointer px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 rounded">
                            النص الكامل المستخرج من الوثيقة — انقر للعرض
                        </summary>
                        <div class="p-3 max-h-96 overflow-y-auto whitespace-pre-wrap font-arabic leading-7 text-sm" dir="rtl">{{ $document->ocr_text }}</div>
                    </details>
                @endif
            </div>
        @endif

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">الإصدارات</h3>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                    <tr>
                        <th class="px-4 py-2 text-start">الإصدار</th>
                        <th class="px-4 py-2 text-start">رفع بواسطة</th>
                        <th class="px-4 py-2 text-start">التاريخ</th>
                        <th class="px-4 py-2 text-start">الحالة</th>
                        <th class="px-4 py-2 text-end"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach ($document->versions->sortByDesc('version_no') as $v)
                        <tr>
                            <td class="px-4 py-2 font-medium">v{{ $v->version_no }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $v->createdBy?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-500">{{ $v->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="px-4 py-2">
                                @if ($v->locked)
                                    <span class="text-xs px-2 py-1 rounded bg-gray-200 text-gray-800">مقفل</span>
                                @else
                                    <span class="text-xs px-2 py-1 rounded bg-amber-100 text-amber-800">قابل للتعديل</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-end">
                                <a href="{{ route('documents.download', $v) }}"
                                   class="text-lexa-600 hover:text-lexa-800">تنزيل</a>
                                @unless ($v->locked)
                                    <button wire:click="lockVersion({{ $v->id }})"
                                            wire:confirm="قفل هذا الإصدار يمنع تعديله. متابعة؟"
                                            class="ms-3 text-gray-600 hover:text-gray-900">قفل</button>
                                @endunless
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">رفع إصدار جديد</h3>
            <input wire:model="newVersionFile" type="file" class="block w-full text-sm" />
            <div wire:loading wire:target="newVersionFile" class="mt-2 text-sm text-gray-500">جاري الرفع...</div>
            @error('newVersionFile') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            <div class="mt-3">
                <button wire:click="uploadNewVersion"
                        class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md">
                    رفع كإصدار جديد
                </button>
            </div>
        </div>
    </div>
</div>
