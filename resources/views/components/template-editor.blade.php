@props([
    'wireKey' => 'body',     // Livewire property to sync into
    'initial' => '',         // initial textarea value
    'rows' => 22,
])
@php
    // Build all PHP-side values here, OUTSIDE any @js(...) call. Blade's
    // naive paren counter in `@js(...)` mis-parses casts and other inner
    // parens, so we keep @js arguments to bare variable references only.
    $groups = \App\Services\Templates\VariableCatalog::groupTokens();
    $initialValue = $initial === null ? '' : (string) $initial;
@endphp

{{-- Alpine factory `window.lexaTemplateEditor` is defined in
     resources/js/template-editor.js (bundled by Vite). We deliberately do
     NOT inline a <script> block here — Blade's tokenizer scans <script>
     content for echo/directive patterns and can mis-parse JS regex
     literals and template strings as Blade syntax. --}}

<div wire:ignore
     class="grid grid-cols-1 lg:grid-cols-[1fr_15rem] gap-3"
     x-data="lexaTemplateEditor({ groups: @js($groups), wireKey: @js($wireKey), initial: @js($initialValue) })"
     x-init="init()">

    {{-- ===== Editor + inline autocomplete popup ===== --}}
    <div class="relative">
        <textarea x-ref="ta"
                  @input="onInput()"
                  @keydown="onKey($event)"
                  @scroll="onScroll()"
                  rows="{{ $rows }}"
                  dir="rtl"
                  spellcheck="false"
                  class="w-full rounded-md border-gray-300 shadow-sm font-mono text-sm leading-7"></textarea>

        <div x-show="ac.open" x-cloak
             :style="ac.style"
             class="absolute z-50 bg-white border border-gray-200 shadow-xl rounded-md max-h-72 overflow-y-auto">
            <div class="px-2 py-1 border-b bg-gray-50 text-[10px] text-gray-500 sticky top-0">
                <span>↑↓ تنقل · Enter/Tab إدراج · Esc إغلاق</span>
            </div>
            <template x-for="(t, i) in acFiltered" :key="t.token">
                <button type="button"
                        @mousedown.prevent="pickAc(t)"
                        @mouseenter="ac.index = i"
                        :class="i === ac.index ? 'bg-lexa-50' : ''"
                        class="block w-full text-start px-3 py-1.5 hover:bg-lexa-50 border-b last:border-b-0">
                    <div class="flex justify-between items-baseline gap-2">
                        <span class="text-sm font-medium text-gray-900" x-text="t.label_ar"></span>
                        <span class="text-[10px] text-gray-400" x-text="t.group"></span>
                    </div>
                    <div class="text-xs text-lexa-700 font-mono" x-text="t.token"></div>
                </button>
            </template>
            <div x-show="acFiltered.length === 0" class="px-3 py-2 text-xs text-gray-500">
                لا توجد متغيرات مطابقة
            </div>
        </div>
    </div>

    {{-- ===== Compact collapsible side panel ===== --}}
    <aside class="bg-gray-50 border rounded-md p-2 text-xs">
        <p class="text-[11px] text-gray-600 mb-2 leading-relaxed">
            <strong>نصيحة:</strong> اكتب <code class="bg-gray-200 px-1 rounded">@{{</code> داخل المحرر لظهور قائمة سريعة بالمتغيرات.
        </p>
        <input x-model="search" type="text"
               placeholder="ابحث: بائع، عنوان، محكمة…"
               class="w-full text-xs rounded border-gray-300 mb-2 py-1 px-2" />

        <div class="space-y-1 max-h-[480px] overflow-y-auto pe-1">
            @foreach ($groups as $i => $group)
                <details {{ $i === 0 ? 'open' : '' }}
                         x-show="groupVisible(@js($group))"
                         class="bg-white rounded border border-gray-200">
                    <summary class="cursor-pointer text-xs font-semibold text-gray-800 px-2 py-1.5 hover:bg-gray-100 rounded">
                        {{ $group['heading'] }}
                    </summary>
                    <div class="px-2 pb-1.5 pt-0.5 space-y-0.5">
                        @foreach ($group['tokens'] as $tk)
                            <button type="button"
                                    x-show="tokenVisible(@js($tk))"
                                    @click="insertSnippet(@js($tk['snippet']))"
                                    class="block w-full text-start text-xs text-gray-700 hover:text-lexa-700 hover:bg-lexa-50 rounded px-1.5 py-0.5"
                                    title="{{ $tk['token'] }}">
                                {{ $tk['label_ar'] }}
                            </button>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </div>
    </aside>
</div>
