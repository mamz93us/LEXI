<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-5xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-gray-900">{{ __('Calendar') }}</h2>
            <div class="flex gap-1 text-sm">
                <button wire:click="$set('mode', 'firm')"
                        class="px-3 py-1.5 rounded-md {{ $mode === 'firm' ? 'bg-lexa-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                    المكتب
                </button>
                <button wire:click="$set('mode', 'mine')"
                        class="px-3 py-1.5 rounded-md {{ $mode === 'mine' ? 'bg-lexa-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                    جلساتي
                </button>
                <button wire:click="$set('mode', 'today')"
                        class="px-3 py-1.5 rounded-md {{ $mode === 'today' ? 'bg-lexa-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' }}">
                    اليوم
                </button>
            </div>
        </div>

        @if (empty($this->days))
            <div class="bg-white shadow-sm rounded-lg p-12 text-center text-gray-500">
                لا توجد جلسات أو مواعيد في هذا النطاق.
            </div>
        @else
            <div class="space-y-3">
                @foreach ($this->days as $day)
                    <div class="bg-white shadow-sm rounded-lg p-4">
                        <h3 class="font-semibold text-gray-900 mb-2">
                            {{ $day['date']->format('Y-m-d') }}
                            <span class="text-xs text-gray-500 ms-2">{{ $day['date']->locale('ar')->dayName }}</span>
                        </h3>
                        @foreach (($day['hearings'] ?? []) as $hearing)
                            <div class="text-sm py-1 border-s-2 border-blue-200 ps-3 mb-1">
                                <a href="{{ route('cases.show', $hearing->case) }}" wire:navigate class="hover:underline">
                                    جلسة — {{ $hearing->case?->case_number }} — {{ $hearing->purpose ?? '—' }}
                                </a>
                            </div>
                        @endforeach
                        @foreach (($day['deadlines'] ?? []) as $deadline)
                            <div class="text-sm py-1 border-s-2 border-amber-300 ps-3 mb-1">
                                ميعاد طعن — {{ $deadline->type }}
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
