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
        </div>

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
