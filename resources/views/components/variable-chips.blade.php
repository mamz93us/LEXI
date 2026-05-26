@props([
    'target' => 'body', // the textarea Livewire property name to insert into
])
@php
    $groups = \App\Services\Templates\VariableCatalog::groupTokens();
@endphp

@once
    <script>
        window.lexaVariableChips = function ({ target }) {
            return {
                search: '',
                textarea: null,
                init() {
                    this.$nextTick(() => { this.refreshTarget(); });
                },
                refreshTarget() {
                    this.textarea = document.querySelector(`textarea[wire\\:model="${target}"]`)
                        ?? document.querySelector(`textarea[wire\\:model\\.lazy="${target}"]`)
                        ?? document.querySelector(`textarea[wire\\:model\\.live="${target}"]`);
                },
                matches(haystack) {
                    if (!this.search) return true;
                    return haystack.toLowerCase().includes(this.search.toLowerCase());
                },
                groupVisible(g) {
                    if (!this.search) return true;
                    if (this.matches(g.heading)) return true;
                    return g.tokens.some(t => this.tokenVisible(t));
                },
                tokenVisible(t) {
                    return this.matches(t.label_ar) || this.matches(t.token);
                },
                insert(snippet) {
                    if (!this.textarea) this.refreshTarget();
                    if (!this.textarea) return;
                    const ta = this.textarea;
                    const start = ta.selectionStart ?? ta.value.length;
                    const end = ta.selectionEnd ?? ta.value.length;
                    const before = ta.value.substring(0, start);
                    const after = ta.value.substring(end);
                    ta.value = before + snippet + after;
                    const newPos = start + snippet.length;
                    ta.focus();
                    ta.setSelectionRange(newPos, newPos);
                    ta.dispatchEvent(new Event('input', { bubbles: true }));
                    ta.dispatchEvent(new Event('change', { bubbles: true }));
                },
            };
        };
    </script>
@endonce

<div class="bg-gray-50 border rounded-md p-3"
     x-data="lexaVariableChips({ target: @js($target) })"
     x-init="init()">
    <div class="flex items-center justify-between mb-2">
        <h4 class="text-sm font-semibold text-gray-900">المتغيرات الجاهزة</h4>
        <p class="text-xs text-gray-500">انقر لإدراج في الموضع الحالي للمؤشر</p>
    </div>

    <input type="text" placeholder="ابحث (مثال: البائع، العنوان، المحكمة)"
           x-model="search"
           class="w-full text-xs rounded border-gray-300 shadow-sm mb-2 py-1 px-2" />

    <div class="space-y-3 max-h-[480px] overflow-y-auto pe-1">
        @foreach ($groups as $group)
            <div x-show="groupVisible(@js($group))">
                <p class="text-xs font-semibold text-gray-700 mb-1">{{ $group['heading'] }}</p>
                <div class="flex flex-wrap gap-1">
                    @foreach ($group['tokens'] as $tk)
                        <button type="button"
                                x-show="tokenVisible(@js($tk))"
                                @click.prevent="insert(@js($tk['snippet']))"
                                class="text-xs px-2 py-1 bg-white border border-gray-300 rounded hover:bg-lexa-50 hover:border-lexa-300 transition"
                                title="{{ $tk['token'] }}">
                            {{ $tk['label_ar'] }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
