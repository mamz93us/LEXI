@props([
    'wireKey' => 'body',     // Livewire property to sync into
    'initial' => '',         // initial textarea value
    'rows' => 22,
])
@php
    $groups = \App\Services\Templates\VariableCatalog::groupTokens();
@endphp

@once
    <script>
        window.lexaTemplateEditor = function (config) {
            return {
                groups: config.groups,
                wireKey: config.wireKey,
                search: '',
                ac: {
                    open: false,
                    query: '',
                    triggerStart: -1,
                    index: 0,
                    style: 'top: 100%; inset-inline-start: 0; width: 22rem;',
                },

                init() {
                    this.$refs.ta.value = config.initial;
                    // One initial sync so a save right after open carries the seed value.
                    this.$nextTick(() => this.sync(true));

                    // Close popup when the user clicks anywhere outside this editor.
                    this._onDocClick = (e) => {
                        if (!this.$el.contains(e.target)) this.ac.open = false;
                    };
                    document.addEventListener('click', this._onDocClick);
                },

                // Push the textarea's current value into Livewire's JS-side state.
                // The 3rd arg `false` means: don't trigger a server round-trip / re-render
                // — Livewire keeps the new value in memory and ships it on the next action
                // (e.g. wire:submit). This is what keeps undo history alive: the DOM
                // textarea is never replaced, only its value is mirrored.
                sync(deferred = false) {
                    if (!this.$refs.ta) return;
                    this.$wire.set(this.wireKey, this.$refs.ta.value, false);
                },

                onInput() {
                    this.detectTrigger();
                    if (this._syncTimer) clearTimeout(this._syncTimer);
                    this._syncTimer = setTimeout(() => this.sync(), 250);
                },

                onScroll() {
                    if (this.ac.open) this.positionPopup();
                },

                // Detect `{{` followed by an optional token-name partial, with the
                // cursor sitting at the end of it. Trigger the autocomplete then.
                detectTrigger() {
                    const ta = this.$refs.ta;
                    const pos = ta.selectionStart;
                    const before = ta.value.substring(0, pos);
                    // Match: `{{` (allow whitespace after) + optional identifier(.identifier)*
                    const m = before.match(/\{\{\s*([a-zA-Z_][a-zA-Z0-9_.]*)?$/);
                    if (m) {
                        this.ac.open = true;
                        this.ac.query = (m[1] || '').toLowerCase();
                        this.ac.triggerStart = pos - m[0].length;
                        this.ac.index = 0;
                        this.positionPopup();
                    } else {
                        this.ac.open = false;
                    }
                },

                // Position the autocomplete just under the line where the caret sits.
                // Uses a hidden mirror div that copies the textarea's text and font
                // metrics — a well-known technique because textareas do not expose
                // caret pixel coordinates directly.
                positionPopup() {
                    const ta = this.$refs.ta;
                    if (!ta) return;

                    let mirror = document.getElementById('lexa-caret-mirror');
                    if (!mirror) {
                        mirror = document.createElement('div');
                        mirror.id = 'lexa-caret-mirror';
                        mirror.setAttribute('aria-hidden', 'true');
                        Object.assign(mirror.style, {
                            position: 'absolute',
                            visibility: 'hidden',
                            whiteSpace: 'pre-wrap',
                            wordWrap: 'break-word',
                            top: '0',
                            left: '-9999px',
                            overflow: 'hidden',
                        });
                        document.body.appendChild(mirror);
                    }
                    const cs = window.getComputedStyle(ta);
                    [
                        'fontFamily','fontSize','fontWeight','fontStyle','letterSpacing',
                        'textTransform','wordSpacing','lineHeight','paddingTop','paddingRight',
                        'paddingBottom','paddingLeft','borderTopWidth','borderRightWidth',
                        'borderBottomWidth','borderLeftWidth','boxSizing','direction',
                    ].forEach(p => mirror.style[p] = cs[p]);
                    mirror.style.width = ta.clientWidth + 'px';
                    mirror.style.height = 'auto';

                    const before = ta.value.substring(0, ta.selectionStart);
                    mirror.textContent = before;
                    const marker = document.createElement('span');
                    marker.textContent = '​';
                    mirror.appendChild(marker);

                    const lineHeight = parseFloat(cs.lineHeight) || (parseFloat(cs.fontSize) * 1.4);
                    const top = marker.offsetTop - ta.scrollTop + lineHeight + 4;
                    const left = marker.offsetLeft - ta.scrollLeft;

                    // Clamp inside the textarea so the popup never escapes wildly.
                    const maxTop = ta.clientHeight + ta.offsetTop;
                    const clampedTop = Math.min(top, maxTop);

                    // For RTL, the start side is the right; we offset from inset-inline-start.
                    this.ac.style =
                        `top: ${ta.offsetTop + clampedTop}px;` +
                        `inset-inline-start: ${ta.offsetLeft + left}px;` +
                        `width: 22rem;`;
                },

                get acFiltered() {
                    const q = this.ac.query;
                    const flat = this.groups.flatMap(g =>
                        g.tokens.map(t => ({...t, group: g.heading}))
                    );
                    if (!q) return flat.slice(0, 50);
                    return flat.filter(t =>
                        t.token.toLowerCase().includes(q) ||
                        t.label_ar.includes(q) ||
                        (t.group || '').includes(q)
                    ).slice(0, 50);
                },

                onKey(e) {
                    if (!this.ac.open) return;
                    const visible = this.acFiltered;
                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        this.ac.index = Math.min(visible.length - 1, this.ac.index + 1);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        this.ac.index = Math.max(0, this.ac.index - 1);
                    } else if (e.key === 'Enter' || e.key === 'Tab') {
                        if (visible[this.ac.index]) {
                            e.preventDefault();
                            this.pickAc(visible[this.ac.index]);
                        }
                    } else if (e.key === 'Escape') {
                        e.preventDefault();
                        this.ac.open = false;
                    }
                },

                pickAc(t) {
                    const ta = this.$refs.ta;
                    const before = ta.value.substring(0, this.ac.triggerStart);
                    const after = ta.value.substring(ta.selectionStart);
                    ta.value = before + t.snippet + after;
                    const newPos = before.length + t.snippet.length;
                    ta.focus();
                    ta.setSelectionRange(newPos, newPos);
                    this.ac.open = false;
                    this.sync();
                },

                insertSnippet(snippet) {
                    const ta = this.$refs.ta;
                    const start = ta.selectionStart ?? ta.value.length;
                    const end = ta.selectionEnd ?? ta.value.length;
                    const before = ta.value.substring(0, start);
                    const after = ta.value.substring(end);
                    ta.value = before + snippet + after;
                    const newPos = before.length + snippet.length;
                    ta.focus();
                    ta.setSelectionRange(newPos, newPos);
                    this.sync();
                },

                groupVisible(g) {
                    if (!this.search) return true;
                    if (g.heading.includes(this.search)) return true;
                    return g.tokens.some(t => this.tokenVisible(t));
                },
                tokenVisible(t) {
                    if (!this.search) return true;
                    const q = this.search.toLowerCase();
                    return t.label_ar.includes(this.search) || t.token.toLowerCase().includes(q);
                },
            };
        };
    </script>
@endonce

<div wire:ignore
     class="grid grid-cols-1 lg:grid-cols-[1fr_15rem] gap-3"
     x-data="lexaTemplateEditor({ groups: @js($groups), wireKey: @js($wireKey), initial: @js((string) $initial) })"
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
