<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">سجل التدقيق</h2>
            <p class="text-sm text-gray-500 mt-1">كل إنشاء وتعديل وحذف للبيانات الحساسة (العملاء، القضايا، المستندات، التوكيلات، الشركات) — مع المستخدم والوقت والتغييرات.</p>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-3 mb-4 grid grid-cols-1 sm:grid-cols-3 gap-2">
            <select wire:model.live="action_filter" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">— كل العمليات —</option>
                <option value="created">إنشاء</option>
                <option value="updated">تعديل</option>
                <option value="deleted">حذف</option>
                <option value="viewed">اطّلاع</option>
            </select>
            <select wire:model.live="type_filter" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">— كل الأنواع —</option>
                @foreach ($this->types as $t)
                    <option value="{{ $t }}">{{ class_basename($t) }}</option>
                @endforeach
            </select>
        </div>

        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->logs->isEmpty())
                <div class="p-12 text-center text-gray-500">لا توجد سجلات.</div>
            @else
                <div class="divide-y">
                    @foreach ($this->logs as $log)
                        @php
                            $actionColor = [
                                'created' => 'bg-green-100 text-green-800',
                                'updated' => 'bg-amber-100 text-amber-800',
                                'deleted' => 'bg-red-100 text-red-800',
                                'viewed' => 'bg-gray-100 text-gray-700',
                            ][$log->action] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <div class="p-4 hover:bg-gray-50">
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="text-xs px-2 py-0.5 rounded {{ $actionColor }}">{{ $log->actionLabelAr() }}</span>
                                <span class="font-medium text-gray-900">{{ class_basename($log->auditable_type) }}</span>
                                <span class="text-gray-400">#{{ $log->auditable_id }}</span>
                                <span class="text-gray-500">·</span>
                                <span class="text-gray-700">{{ $log->user?->name ?? 'النظام' }}</span>
                                <span class="text-gray-400 text-xs ms-auto">{{ $log->created_at?->format('Y-m-d H:i:s') }}</span>
                                @if ($log->ip_address)
                                    <span class="text-gray-400 text-xs font-mono">{{ $log->ip_address }}</span>
                                @endif
                            </div>

                            @if ($log->action === 'updated' && $log->after)
                                <div class="mt-2 text-xs bg-gray-50 rounded p-2 overflow-x-auto" dir="ltr">
                                    <table class="text-xs">
                                        <tbody>
                                            @foreach ($log->after as $field => $newVal)
                                                <tr>
                                                    <td class="pe-3 text-gray-500 align-top font-mono">{{ $field }}</td>
                                                    <td class="pe-2 text-red-600 align-top line-through">{{ \Illuminate\Support\Str::limit((string) ($log->before[$field] ?? '∅'), 80) }}</td>
                                                    <td class="text-green-700 align-top">{{ \Illuminate\Support\Str::limit(is_scalar($newVal) ? (string) $newVal : json_encode($newVal, JSON_UNESCAPED_UNICODE), 80) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="p-3 border-t">
                    {{ $this->logs->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
