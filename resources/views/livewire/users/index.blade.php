<div class="py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
            <div>
                <h2 class="text-2xl font-semibold text-gray-900">المستخدمون والمحامون</h2>
                <p class="text-sm text-gray-500 mt-1">إدارة فريق المكتب — الشركاء والمحامون والمساعدون. يستخدم الذكاء الاصطناعي بيانات المحامين كأطراف للتوكيلات حين تختار «من محاميي المكتب».</p>
            </div>
            <a href="{{ route('users.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-lexa-600 hover:bg-lexa-700 text-white text-sm font-medium rounded-md transition">
                + مستخدم جديد
            </a>
        </div>

        @if (session('saved'))
            <div class="mb-4 bg-green-50 border-s-4 border-green-500 p-3 text-sm text-green-800">{{ session('saved') }}</div>
        @endif
        @if (session('error'))
            <div class="mb-4 bg-red-50 border-s-4 border-red-500 p-3 text-sm text-red-800">{{ session('error') }}</div>
        @endif

        {{-- Filters --}}
        <div class="bg-white shadow-sm rounded-lg p-3 mb-4 grid grid-cols-1 sm:grid-cols-4 gap-2">
            <input wire:model.live.debounce.300ms="search" type="text"
                   placeholder="ابحث بالاسم، البريد، الرقم القومي، رقم النقابة…"
                   class="rounded-md border-gray-300 shadow-sm text-sm" />
            <select wire:model.live="role_filter" class="rounded-md border-gray-300 shadow-sm text-sm">
                <option value="">— كل الأدوار —</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                @endforeach
            </select>
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input wire:model.live="show_inactive" type="checkbox" class="rounded border-gray-300" />
                إظهار المعطّلين
            </label>
            <div class="text-sm text-gray-500 self-center text-end">
                {{ $this->users->count() }} مستخدم
            </div>
        </div>

        {{-- Table --}}
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            @if ($this->users->isEmpty())
                <div class="p-12 text-center text-gray-500">
                    لا يوجد مستخدمون مطابقون.
                </div>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-600">
                        <tr class="text-start">
                            <th class="px-4 py-2 text-start">الاسم</th>
                            <th class="px-4 py-2 text-start">الدور</th>
                            <th class="px-4 py-2 text-start">البريد</th>
                            <th class="px-4 py-2 text-start">الهاتف</th>
                            <th class="px-4 py-2 text-start">الرقم القومي</th>
                            <th class="px-4 py-2 text-start">رقم النقابة</th>
                            <th class="px-4 py-2 text-start">الحالة</th>
                            <th class="px-4 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach ($this->users as $u)
                            <tr class="hover:bg-gray-50 {{ $u->is_active ? '' : 'opacity-60' }}">
                                <td class="px-4 py-2">
                                    <div class="font-medium text-gray-900">{{ $u->name_ar ?: $u->name }}</div>
                                    @if ($u->name_ar && $u->name && $u->name_ar !== $u->name)
                                        <div class="text-xs text-gray-500">{{ $u->name }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-2">
                                    <span class="text-xs px-2 py-0.5 rounded
                                        @if ($u->role?->value === 'partner') bg-purple-100 text-purple-800
                                        @elseif ($u->role?->value === 'admin') bg-red-100 text-red-800
                                        @elseif ($u->role?->value === 'associate') bg-blue-100 text-blue-800
                                        @elseif ($u->role?->value === 'paralegal') bg-amber-100 text-amber-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $u->role?->label() ?? '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-gray-700">{{ $u->email }}</td>
                                <td class="px-4 py-2 text-gray-700">{{ $u->phone ?: '—' }}</td>
                                <td class="px-4 py-2 text-gray-700">
                                    @if ($u->national_id)<code class="text-xs">{{ $u->national_id }}</code>@else — @endif
                                </td>
                                <td class="px-4 py-2 text-gray-700">{{ $u->bar_association_no ?: '—' }}</td>
                                <td class="px-4 py-2">
                                    @if ($u->is_active)
                                        <span class="text-xs px-2 py-0.5 rounded bg-green-100 text-green-800">نشط</span>
                                    @else
                                        <span class="text-xs px-2 py-0.5 rounded bg-gray-200 text-gray-700">معطّل</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-end whitespace-nowrap">
                                    <a href="{{ route('users.edit', $u->id) }}" wire:navigate
                                       class="text-lexa-700 hover:text-lexa-900 text-xs">تعديل</a>
                                    @if ($u->is_active)
                                        <button wire:click="deactivate({{ $u->id }})"
                                                wire:confirm="تعطيل وصول هذا المستخدم؟"
                                                class="text-red-600 hover:text-red-800 text-xs ms-2">تعطيل</button>
                                    @else
                                        <button wire:click="activate({{ $u->id }})"
                                                class="text-green-700 hover:text-green-900 text-xs ms-2">تفعيل</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
