<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-6">

        {{-- Header --}}
        <div class="bg-white shadow-sm rounded-lg p-6 flex flex-wrap justify-between items-start gap-3">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">{{ $company->name_ar ?: $company->name }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ ['llc' => 'ذ.م.م', 'jsc' => 'مساهمة', 'sole' => 'فردية', 'branch' => 'فرع'][$company->legal_form] ?? $company->legal_form }}
                    @if ($company->commercial_register_no) · س.ت {{ $company->commercial_register_no }} @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('companies.edit', $company) }}" wire:navigate class="text-sm text-lexa-700 hover:text-lexa-900">تعديل</a>
                <a href="{{ route('companies.index') }}" wire:navigate class="text-sm text-gray-500 hover:text-gray-700">← الشركات</a>
            </div>
        </div>

        {{-- ===== Shareholders ===== --}}
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">المساهمون / الشركاء</h3>
                @php $total = $this->ownershipTotal; @endphp
                <span class="text-xs px-2 py-1 rounded {{ abs($total - 100) < 0.001 ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' }}">
                    إجمالي الحصص: {{ rtrim(rtrim(number_format($total, 2), '0'), '.') }}%
                </span>
            </div>

            @if ($this->shareholders->isNotEmpty())
                <table class="w-full text-sm mb-4">
                    <thead class="text-xs text-gray-500">
                        <tr><th class="text-start py-1">الاسم</th><th class="text-start py-1">الحصة</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($this->shareholders as $sh)
                            <tr>
                                <td class="py-2">{{ $sh->display_name }}</td>
                                <td class="py-2">{{ rtrim(rtrim(number_format((float) $sh->ownership_pct, 2), '0'), '.') }}%</td>
                                <td class="py-2 text-end">
                                    <button wire:click="removeShareholder({{ $sh->id }})" wire:confirm="حذف هذا المساهم؟"
                                            class="text-xs text-red-600 hover:text-red-800">حذف</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-sm text-gray-500 mb-4">لا يوجد مساهمون مسجّلون.</p>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2 border-t pt-3">
                <select wire:model="sh_client_id" class="rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">— عميل (اختياري) —</option>
                    @foreach ($this->clientsList as $cl)
                        <option value="{{ $cl->id }}">{{ $cl->name_ar ?: $cl->name }}</option>
                    @endforeach
                </select>
                <input wire:model="sh_display_name" type="text" placeholder="الاسم (إن لم يكن عميلاً)" class="rounded-md border-gray-300 shadow-sm text-sm" />
                <input wire:model="sh_ownership_pct" type="number" step="0.01" min="0" max="100" placeholder="الحصة %" class="rounded-md border-gray-300 shadow-sm text-sm" />
                <button wire:click="addShareholder" class="px-3 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm rounded-md">إضافة</button>
            </div>
            @error('sh_ownership_pct') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('sh_display_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        {{-- ===== Formation steps ===== --}}
        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">خطوات التأسيس</h3>

            @if ($this->steps->isNotEmpty())
                <ol class="space-y-2 mb-4">
                    @foreach ($this->steps as $step)
                        @php
                            $statusMeta = [
                                'pending' => ['قيد الانتظار', 'bg-gray-100 text-gray-700'],
                                'in_progress' => ['جارٍ', 'bg-blue-100 text-blue-800'],
                                'done' => ['مكتمل', 'bg-green-100 text-green-800'],
                                'blocked' => ['متوقف', 'bg-red-100 text-red-800'],
                            ][$step->status] ?? [$step->status, 'bg-gray-100'];
                        @endphp
                        <li class="flex flex-wrap items-center gap-2 border rounded-md p-2">
                            <span class="text-xs text-gray-400">#{{ $step->step_order }}</span>
                            <span class="font-medium text-sm flex-1">{{ $step->title }}</span>
                            @if ($step->authority) <span class="text-xs text-gray-500">{{ $step->authority }}</span> @endif
                            @if ($step->expected_date) <span class="text-xs text-gray-400">متوقع {{ $step->expected_date->format('Y-m-d') }}</span> @endif
                            <span class="text-xs px-2 py-0.5 rounded {{ $statusMeta[1] }}">{{ $statusMeta[0] }}</span>
                            <select wire:change="setStepStatus({{ $step->id }}, $event.target.value)" class="text-xs rounded border-gray-300 py-0.5">
                                <option value="">تغيير…</option>
                                <option value="pending">قيد الانتظار</option>
                                <option value="in_progress">جارٍ</option>
                                <option value="done">مكتمل</option>
                                <option value="blocked">متوقف</option>
                            </select>
                            <button wire:click="removeStep({{ $step->id }})" wire:confirm="حذف الخطوة؟" class="text-xs text-red-600 hover:text-red-800">حذف</button>
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="text-sm text-gray-500 mb-4">لا توجد خطوات بعد.</p>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 border-t pt-3">
                <input wire:model="fs_title" type="text" placeholder="عنوان الخطوة" class="sm:col-span-2 rounded-md border-gray-300 shadow-sm text-sm" />
                <input wire:model="fs_authority" type="text" placeholder="الجهة (GAFI، الشهر العقاري…)" class="rounded-md border-gray-300 shadow-sm text-sm" />
                <input wire:model="fs_expected_date" type="date" class="rounded-md border-gray-300 shadow-sm text-sm" />
                <button wire:click="addStep" class="px-3 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm rounded-md">إضافة خطوة</button>
            </div>
            @error('fs_title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>
</div>
