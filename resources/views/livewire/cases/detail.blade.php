<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto space-y-6">
        <div class="bg-white shadow-sm rounded-lg p-6">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-900">{{ $case->title }}</h2>
                    <p class="text-sm text-gray-500 mt-1">{{ $case->case_number }}</p>
                </div>
                <a href="{{ route('cases.edit', $case) }}" wire:navigate
                   class="text-sm text-lexa-700 hover:text-lexa-900">{{ __('Edit') }}</a>
            </div>

            <dl class="grid grid-cols-2 sm:grid-cols-4 gap-4 mt-6 text-sm">
                <div><dt class="text-gray-500">الدرجة</dt><dd>{{ $case->degree }}</dd></div>
                <div><dt class="text-gray-500">نوع القضية</dt><dd>{{ $case->caseType?->name_ar ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">المحكمة</dt><dd>{{ $case->court?->name_ar ?? '—' }}</dd></div>
                <div><dt class="text-gray-500">{{ __('Status') }}</dt><dd>{{ $case->status }}</dd></div>
            </dl>
        </div>

        @if ($chain->count() > 1)
            <div class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">سلسلة القضية</h3>
                <ol class="space-y-1 text-sm">
                    @foreach ($chain as $stage)
                        <li class="flex items-center gap-2 {{ $stage->id === $case->id ? 'font-semibold text-lexa-700' : 'text-gray-700' }}">
                            <span>{{ $stage->degree }}</span>
                            <span class="text-gray-400">·</span>
                            <a href="{{ route('cases.show', $stage) }}" wire:navigate class="hover:underline">{{ $stage->case_number }}</a>
                        </li>
                    @endforeach
                </ol>
            </div>
        @endif

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">الجلسات</h3>
            @forelse ($hearings as $hearing)
                <div class="border-s-2 border-lexa-100 ps-4 py-3">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium">{{ $hearing->session_date->format('Y-m-d') }} — {{ $hearing->purpose ?? '—' }}</span>
                        @if ($hearing->next_date)
                            <span class="text-gray-500">التالية: {{ $hearing->next_date->format('Y-m-d') }}</span>
                        @endif
                    </div>
                    @if ($hearing->requests->isNotEmpty())
                        <ul class="mt-2 space-y-1 text-sm text-gray-600">
                            @foreach ($hearing->requests as $req)
                                <li>· {{ $req->requestType?->name_ar }} <span class="text-xs text-gray-400">({{ $req->status }})</span></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">لا توجد جلسات بعد.</p>
            @endforelse

            <form wire:submit="saveHearing" class="mt-6 border-t pt-6 space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <input wire:model="hearing_date" type="date" placeholder="تاريخ الجلسة"
                           class="rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" required />
                    <input wire:model="hearing_purpose" type="text" placeholder="الغرض"
                           class="rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                    <input wire:model="hearing_next_date" type="date" placeholder="الجلسة القادمة"
                           class="rounded-md border-gray-300 shadow-sm focus:border-lexa-500 focus:ring-lexa-500" />
                </div>

                <div>
                    <button type="button" wire:click="addRequestLine"
                            class="text-sm text-lexa-700 hover:text-lexa-900">+ إضافة طلب</button>
                </div>

                @foreach ($hearing_requests as $i => $line)
                    <div class="grid grid-cols-1 sm:grid-cols-5 gap-2 items-center text-sm" wire:key="req-{{ $i }}">
                        <select wire:model="hearing_requests.{{ $i }}.type_id"
                                class="rounded-md border-gray-300 shadow-sm sm:col-span-2">
                            <option value="">— نوع الطلب —</option>
                            @foreach ($requestTypes as $rt)
                                <option value="{{ $rt->id }}">{{ $rt->name_ar }}</option>
                            @endforeach
                        </select>
                        <select wire:model="hearing_requests.{{ $i }}.party"
                                class="rounded-md border-gray-300 shadow-sm">
                            <option value="claimant">مدعي</option>
                            <option value="defendant">مدعى عليه</option>
                            <option value="other">أخرى</option>
                        </select>
                        <select wire:model="hearing_requests.{{ $i }}.status"
                                class="rounded-md border-gray-300 shadow-sm">
                            <option value="pending">pending</option>
                            <option value="granted">granted</option>
                            <option value="rejected">rejected</option>
                            <option value="deferred">deferred</option>
                        </select>
                        <button type="button" wire:click="removeRequestLine({{ $i }})"
                                class="text-red-600 text-sm">حذف</button>
                    </div>
                @endforeach

                <div class="flex justify-end pt-2">
                    <button type="submit" class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm rounded-md">
                        إضافة جلسة
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white shadow-sm rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-3">الأحكام</h3>
            @forelse ($judgments as $judgment)
                <div class="border-s-2 border-lexa-100 ps-4 py-3">
                    <div class="flex justify-between text-sm">
                        <span class="font-medium">{{ $judgment->judgment_date->format('Y-m-d') }} — {{ $judgment->judgmentType?->name_ar }}</span>
                        <span class="text-gray-500">{{ $judgment->presence_type === 'in_presence' ? 'حضوري' : 'غيابي' }}</span>
                    </div>
                    @if ($judgment->summary)
                        <p class="text-sm text-gray-600 mt-1">{{ $judgment->summary }}</p>
                    @endif
                    @if ($judgment->appeal_deadline)
                        <p class="text-sm text-amber-700 mt-1">
                            ميعاد الطعن: {{ $judgment->appeal_deadline->format('Y-m-d') }}
                            <span class="text-xs text-amber-500">(تحقق من العدد مع محامٍ)</span>
                        </p>
                        @if (in_array($case->degree, ['partial', 'first_instance']))
                            <button wire:click="appealThisJudgment({{ $judgment->id }})"
                                    class="mt-2 text-sm text-lexa-700 hover:text-lexa-900">
                                إنشاء استئناف
                            </button>
                        @endif
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">لا توجد أحكام بعد.</p>
            @endforelse

            <form wire:submit="saveJudgment" class="mt-6 border-t pt-6 grid grid-cols-1 sm:grid-cols-4 gap-3">
                <select wire:model="judgment_type_id" class="rounded-md border-gray-300 shadow-sm">
                    <option value="">— نوع الحكم —</option>
                    @foreach ($judgmentTypes as $jt)
                        <option value="{{ $jt->id }}">{{ $jt->name_ar }}</option>
                    @endforeach
                </select>
                <input wire:model="judgment_date" type="date" class="rounded-md border-gray-300 shadow-sm" required />
                <select wire:model="judgment_presence" class="rounded-md border-gray-300 shadow-sm">
                    <option value="in_presence">حضوري</option>
                    <option value="in_absentia">غيابي</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm rounded-md">
                    إضافة حكم
                </button>
            </form>
        </div>
    </div>
</div>
