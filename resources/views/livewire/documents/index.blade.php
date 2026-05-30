@php
    $hasInFlight = $this->documents->contains(fn ($d) => in_array($d->ingestion_status, ['pending', 'ingesting'], true));
@endphp
<div class="py-8 px-4 sm:px-6 lg:px-8"
     @if ($hasInFlight) wire:poll.5s @endif>
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">المستندات</h2>
            <a href="{{ route('documents.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md transition">
                رفع مستند
            </a>
        </div>

        @if (session('saved'))
            <div class="mb-4 bg-green-50 border-s-4 border-green-500 p-3 text-sm text-green-800">{{ session('saved') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 bg-red-50 border-s-4 border-red-500 p-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        <div class="flex flex-wrap gap-3 mb-4">
            <input type="text"
                   wire:model.live.debounce.250ms="search"
                   placeholder="{{ __('Search') }}"
                   class="flex-1 min-w-[200px] rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
            <select wire:model.live="type" class="rounded-md border-gray-300 shadow-sm">
                <option value="">كل الأنواع</option>
                <option value="contract">عقد</option>
                <option value="poa">توكيل</option>
                <option value="memo">مذكرة</option>
                <option value="filing">إيداع</option>
                <option value="other">أخرى</option>
            </select>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->documents->isEmpty())
                <p class="p-6 text-center text-gray-500">لا توجد مستندات بعد.</p>
            @else
                <table class="w-full">
                    <thead class="bg-gray-50 text-xs font-medium text-gray-500 uppercase">
                        <tr>
                            <th class="px-6 py-3 text-start">العنوان</th>
                            <th class="px-6 py-3 text-start">النوع</th>
                            <th class="px-6 py-3 text-start">الإصدار</th>
                            <th class="px-6 py-3 text-start">الفهرسة</th>
                            <th class="px-6 py-3 text-start">آخر تعديل</th>
                            <th class="px-6 py-3 text-end"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($this->documents as $doc)
                            @php
                                $ingest = [
                                    'pending' => ['في الانتظار', 'bg-blue-100 text-blue-800'],
                                    'ingesting' => ['جارٍ…', 'bg-blue-100 text-blue-800 animate-pulse'],
                                    'ingested' => [$doc->embedding_count.' مقطع ✓', 'bg-green-100 text-green-800'],
                                    'skipped' => ['متخطّاة', 'bg-amber-100 text-amber-800'],
                                    'failed' => ['فشلت', 'bg-red-100 text-red-800'],
                                ][$doc->ingestion_status] ?? null;
                            @endphp
                            <tr>
                                <td class="px-6 py-3 text-sm">
                                    <a href="{{ route('documents.show', $doc) }}" wire:navigate
                                       class="text-lexa-700 hover:text-lexa-900 font-medium">{{ $doc->title }}</a>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500">{{ $doc->type }}</td>
                                <td class="px-6 py-3 text-sm text-gray-500">
                                    v{{ $doc->currentVersion?->version_no ?? '—' }}
                                </td>
                                <td class="px-6 py-3 text-sm">
                                    @if ($ingest)
                                        <span class="text-xs px-2 py-0.5 rounded {{ $ingest[1] }}"
                                              @if ($doc->ingestion_note) title="{{ $doc->ingestion_note }}" @endif>
                                            {{ $ingest[0] }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500">
                                    {{ $doc->updated_at?->format('Y-m-d H:i') }}
                                </td>
                                <td class="px-6 py-3 text-end text-sm whitespace-nowrap">
                                    @if ($doc->currentVersion && ! in_array($doc->ingestion_status, ['pending', 'ingesting'], true))
                                        <button type="button"
                                                wire:click="reindex({{ $doc->id }})"
                                                wire:confirm="إعادة فهرسة هذا المستند؟"
                                                class="text-lexa-600 hover:text-lexa-800">إعادة فهرسة</button>
                                    @endif
                                    <a href="{{ route('documents.edit', $doc) }}" wire:navigate
                                       class="ms-3 text-gray-600 hover:text-gray-900">{{ __('Edit') }}</a>
                                    <button type="button"
                                            wire:click="delete({{ $doc->id }})"
                                            wire:confirm="حذف هذا المستند وكل إصداراته؟"
                                            class="ms-3 text-red-600 hover:text-red-800">{{ __('Delete') }}</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
