// LEXA template / clause editor — Alpine factory.
//
// Lives in a real JS file (rather than embedded in a Blade <script> block)
// because Blade's tokenizer scans the entire source — including inside
// <script> tags — for `{{ }}` echoes and `@directive(...)` directives.
// Patterns inside JS regex literals or template strings can collide with
// that and produce broken compiled PHP.
//
// The Blade component (resources/views/components/template-editor.blade.php)
// just emits the HTML scaffolding and calls window.lexaTemplateEditor(...)
// — all parsing-sensitive code lives here, bundled by Vite.

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
        // 3rd arg `false` = don't trigger a server round-trip / re-render —
        // Livewire keeps the new value in memory and ships it on the next
        // action (e.g. wire:submit). This is what keeps undo history alive:
        // the DOM textarea is never replaced, only its value is mirrored.
        sync() {
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

        // Detect "{{" followed by an optional token-name partial, with the
        // cursor sitting at the end of it. Trigger the autocomplete then.
        detectTrigger() {
            const ta = this.$refs.ta;
            const pos = ta.selectionStart;
            const before = ta.value.substring(0, pos);
            const triggerRe = new RegExp(
                '\\{\\{\\s*([a-zA-Z_][a-zA-Z0-9_.]*)?$'
            );
            const m = before.match(triggerRe);
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
        // metrics — textareas don't expose caret pixel coordinates directly.
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
                'fontFamily', 'fontSize', 'fontWeight', 'fontStyle', 'letterSpacing',
                'textTransform', 'wordSpacing', 'lineHeight', 'paddingTop', 'paddingRight',
                'paddingBottom', 'paddingLeft', 'borderTopWidth', 'borderRightWidth',
                'borderBottomWidth', 'borderLeftWidth', 'boxSizing', 'direction',
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
                'top: ' + (ta.offsetTop + clampedTop) + 'px;' +
                'inset-inline-start: ' + (ta.offsetLeft + left) + 'px;' +
                'width: 22rem;';
        },

        get acFiltered() {
            const q = this.ac.query;
            const flat = this.groups.flatMap(g =>
                g.tokens.map(t => Object.assign({}, t, { group: g.heading }))
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
            const start = ta.selectionStart != null ? ta.selectionStart : ta.value.length;
            const end = ta.selectionEnd != null ? ta.selectionEnd : ta.value.length;
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
