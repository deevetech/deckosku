/*
 * DECKO Size Management — premium warm light theme dashboard.
 *
 * Search is the centre of gravity. The hero search bar emits a live typeahead
 * dropdown (top 8 matches) as the user types, while the full result table
 * underneath stays in sync with the same filter state. Keyboard-first:
 * `/` focuses the bar, ↑↓ moves through results, Enter opens, Esc clears.
 *
 * No build step. Loaded after `defer`, so the DOM is ready when init() fires.
 */
window.dashboard = function dashboard() {
    return {
        // ── Persistent UI state ──────────────────────────────────────────────
        query:        '',
        categoryId:   0,
        unitsMode:    'metric',
        cursorIndex:  -1,
        suggestionsOpen: false,
        bookmarksOpen:   false,

        // ── Pagination ───────────────────────────────────────────────────────
        page:        1,
        pageSize:    10,
        get pageCount() { return Math.max(1, Math.ceil(this.results.length / this.pageSize)); },
        get pagedResults() {
            const start = (this.page - 1) * this.pageSize;
            return this.results.slice(start, start + this.pageSize);
        },
        get pageStart() { return this.results.length === 0 ? 0 : (this.page - 1) * this.pageSize + 1; },
        get pageEnd()   { return Math.min(this.page * this.pageSize, this.results.length); },
        // Build a compact page-number list: 1 … (page-1) page (page+1) … last
        get pageNumbers() {
            const total = this.pageCount;
            const cur   = this.page;
            if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
            const pages = new Set([1, total, cur - 1, cur, cur + 1]);
            const sorted = [...pages].filter(p => p >= 1 && p <= total).sort((a, b) => a - b);
            // Insert ellipsis sentinels (string '…') where there are gaps.
            const out = [];
            for (let i = 0; i < sorted.length; i++) {
                out.push(sorted[i]);
                if (i < sorted.length - 1 && sorted[i + 1] - sorted[i] > 1) out.push('…');
            }
            return out;
        },
        goToPage(p) {
            if (p === '…') return;
            const next = Math.max(1, Math.min(this.pageCount, parseInt(p, 10)));
            if (next === this.page) return;
            this.page = next;
            // Scroll the results header into view so the new page doesn't appear "below" the user.
            this.$nextTick(() => {
                document.getElementById('decko-results-top')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        },
        setPageSize(size) {
            this.pageSize = size === 'All' ? Math.max(this.results.length, 1) : parseInt(size, 10);
            this.page = 1;
        },

        // ── Data ─────────────────────────────────────────────────────────────
        results:    [],
        totalCount: 0,
        isLoading:  false,
        active:     null,

        // ── Lightbox ─────────────────────────────────────────────────────────
        lightboxOpen:    false,
        lightboxIndex:   0,
        lightboxItems:   [],
        lightboxZoom:    1,
        lightboxOffsetX: 0,
        lightboxOffsetY: 0,
        lightboxDrag:    null,

        // ── Scanner ──────────────────────────────────────────────────────────
        scannerOpen:  false,
        scannerError: null,
        scannerHint:  'Position a barcode inside the frame.',
        _scanner:     null,

        // ── Pins / recents (localStorage) ────────────────────────────────────
        pins:    [],
        recents: [],
        STORAGE_KEY: 'decko.size.user-state.v4',
        MAX_RECENTS: 10,

        // ── Edit mode (admin) ────────────────────────────────────────────────
        editMode:    false,
        editForm:    {},
        editErrors:  {},
        editSaving:  false,
        editMessage: null,
        get canEdit() { return !!window.DECKO.isAdmin; },

        // Human label for the active category (or '').
        get categoryFilter() {
            const cat = (window.DECKO_BOOT?.categories || []).find((c) => c.id === this.categoryId);
            return cat ? cat.code : '';
        },

        EDIT_GROUPS: [
            { title: 'Identity',
              fields: [
                  { key: 'description',     label: 'Description',  type: 'text' },
                  { key: 'variant_label',   label: 'Variant',      type: 'text' },
                  { key: 'units_per_box',   label: 'Units / box',  type: 'number', step: '1' },
              ] },
            { title: 'Barcodes',
              fields: [
                  { key: 'internal_barcode', label: 'Internal',     type: 'text' },
                  { key: 'gtin_barcode',     label: 'GTIN / EAN',   type: 'text' },
                  { key: 'box_barcode',      label: 'Box',          type: 'text' },
              ] },
            { title: 'Unit · metric',
              fields: [
                  { key: 'unit_length_cm', label: 'L (cm)', type: 'number', step: '0.01' },
                  { key: 'unit_width_cm',  label: 'W (cm)', type: 'number', step: '0.01' },
                  { key: 'unit_height_cm', label: 'H (cm)', type: 'number', step: '0.01' },
                  { key: 'unit_weight_kg', label: 'kg',     type: 'number', step: '0.001' },
                  { key: 'unit_volume_cm3', label: 'cm³',   type: 'number', step: '0.01' },
              ] },
            { title: 'Box · metric',
              fields: [
                  { key: 'box_length_cm', label: 'L (cm)', type: 'number', step: '0.01' },
                  { key: 'box_width_cm',  label: 'W (cm)', type: 'number', step: '0.01' },
                  { key: 'box_height_cm', label: 'H (cm)', type: 'number', step: '0.01' },
                  { key: 'box_weight_kg', label: 'kg',     type: 'number', step: '0.001' },
                  { key: 'box_volume_cm3', label: 'cm³',   type: 'number', step: '0.01' },
              ] },
        ],

        EDIT_IMPERIAL_GROUPS: [
            { title: 'Unit · imperial',
              fields: [
                  { key: 'unit_length_in', label: 'L (in)', type: 'number', step: '0.0001' },
                  { key: 'unit_width_in',  label: 'W (in)', type: 'number', step: '0.0001' },
                  { key: 'unit_height_in', label: 'H (in)', type: 'number', step: '0.0001' },
                  { key: 'unit_weight_lb', label: 'lb',     type: 'number', step: '0.0001' },
                  { key: 'unit_volume_in3', label: 'in³',   type: 'number', step: '0.0001' },
              ] },
            { title: 'Box · imperial',
              fields: [
                  { key: 'box_length_in', label: 'L (in)', type: 'number', step: '0.0001' },
                  { key: 'box_width_in',  label: 'W (in)', type: 'number', step: '0.0001' },
                  { key: 'box_height_in', label: 'H (in)', type: 'number', step: '0.0001' },
                  { key: 'box_weight_lb', label: 'lb',     type: 'number', step: '0.0001' },
                  { key: 'box_volume_in3', label: 'in³',   type: 'number', step: '0.0001' },
              ] },
        ],

        // ── Lifecycle ────────────────────────────────────────────────────────
        init() {
            this.loadUserState();
            this.bindKeyboard();
            this.fetchSkus(/* initial */ true);
            // Autofocus the hero search bar.
            this.$nextTick(() => this.$refs.search?.focus());
        },

        // ── Keyboard bindings ────────────────────────────────────────────────
        bindKeyboard() {
            window.addEventListener('keydown', (e) => {
                if (this.lightboxOpen) {
                    if (e.key === 'Escape')          this.closeLightbox();
                    else if (e.key === 'ArrowRight') this.lightboxNext();
                    else if (e.key === 'ArrowLeft')  this.lightboxPrev();
                    else if (e.key === '+' || e.key === '=') this.lightboxSetZoom(this.lightboxZoom + 0.5);
                    else if (e.key === '-' || e.key === '_') this.lightboxSetZoom(this.lightboxZoom - 0.5);
                    else if (e.key === '0')          this.lightboxResetZoom();
                    return;
                }
                if (this.bookmarksOpen) { if (e.key === 'Escape') this.bookmarksOpen = false; return; }
                if (this.scannerOpen)   { if (e.key === 'Escape') this.closeScanner();         return; }

                const tag = (document.activeElement?.tagName || '').toLowerCase();
                const inField = tag === 'input' || tag === 'textarea' || tag === 'select';

                // `/` focuses search from anywhere outside an input.
                if (e.key === '/' && !inField) {
                    e.preventDefault();
                    this.$refs.search?.focus();
                    this.suggestionsOpen = !!this.query;
                    return;
                }

                if (inField) return;

                if (e.key === 'Escape' && this.active) { this.active = null; this.cancelEditing(); }
            });
        },

        // ── Search input + key handling on the bar itself ────────────────────
        onSearchInput() {
            this.cursorIndex = -1;
            this.suggestionsOpen = !!this.query.trim();
            this.page = 1;
            this.fetchSkus();
        },

        onSearchKey(e) {
            if (e.key === 'Enter') {
                if (this.results.length === 0) return;
                e.preventDefault();
                const idx = this.cursorIndex >= 0 ? this.cursorIndex : 0;
                const row = this.results[idx];
                if (row) {
                    this.suggestionsOpen = false;
                    this.openDetail(row.sku);
                    this.$refs.search?.blur();
                }
                return;
            }

            if (e.key === 'ArrowDown' && this.results.length) {
                e.preventDefault();
                const max = Math.min(7, this.results.length - 1);
                this.cursorIndex = this.cursorIndex < max ? this.cursorIndex + 1 : 0;
                this.suggestionsOpen = true;
                return;
            }
            if (e.key === 'ArrowUp' && this.results.length) {
                e.preventDefault();
                const max = Math.min(7, this.results.length - 1);
                this.cursorIndex = this.cursorIndex <= 0 ? max : this.cursorIndex - 1;
                this.suggestionsOpen = true;
                return;
            }
            if (e.key === 'Escape') {
                if (this.suggestionsOpen) { this.suggestionsOpen = false; return; }
                if (this.active) { this.active = null; this.cancelEditing(); return; }
                if (this.query) { this.query = ''; this.cursorIndex = -1; this.fetchSkus(); return; }
                this.$refs.search?.blur();
            }
        },

        clearAll() {
            this.query = '';
            this.categoryId = 0;
            this.cursorIndex = -1;
            this.suggestionsOpen = false;
            this.page = 1;
            this.fetchSkus();
        },

        setCategory(id) {
            this.categoryId = id;
            this.cursorIndex = -1;
            this.page = 1;
            this.fetchSkus();
        },

        // ── Data fetchers ────────────────────────────────────────────────────
        async fetchSkus() {
            const params = new URLSearchParams();
            const q = (this.query || '').trim();
            if (q) params.set('q', q);
            if (this.categoryId) params.set('c', String(this.categoryId));

            this.isLoading = true;
            try {
                const res = await fetch(`${DECKO.url}api/skus?${params.toString()}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' },
                });
                if (!res.ok) throw new Error(`API ${res.status}`);
                const data = await res.json();
                this.results    = data.results || [];
                this.totalCount = data.total ?? 0;
                // Clamp page if results shrunk below current page.
                if (this.page > this.pageCount) this.page = this.pageCount;
            } catch (err) {
                console.warn('[decko] search failed:', err);
                this.results = [];
                this.totalCount = 0;
            } finally {
                this.isLoading = false;
            }
        },

        async openDetail(sku) {
            const cached = this.results.find((r) => r.sku === sku);
            if (cached) { this.active = cached; this.recordRecent(cached); }
            this.suggestionsOpen = false;
            try {
                const res = await fetch(`${DECKO.url}api/sku/${encodeURIComponent(sku)}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' },
                });
                if (res.ok) {
                    this.active = await res.json();
                    this.recordRecent(this.active);
                }
            } catch (err) { console.warn('[decko] detail failed:', err); }
        },

        // Suggestion → detail. Same as openDetail but signals the typeahead row click.
        openDetailFromSuggestion(sku, idx) {
            this.cursorIndex = idx;
            this.openDetail(sku);
        },

        csvUrl() {
            const p = new URLSearchParams();
            const q = (this.query || '').trim();
            if (q) p.set('q', q);
            if (this.categoryId) p.set('c', String(this.categoryId));
            const qs = p.toString();
            return `${DECKO.url}export.csv${qs ? '?' + qs : ''}`;
        },

        resultsCount() {
            if (this.isLoading && this.results.length === 0) return '…';
            return this.totalCount;
        },

        // ── Number formatting ────────────────────────────────────────────────
        fmt(value, digits = 1) {
            if (value === null || value === undefined || isNaN(value)) return '—';
            const n = Number(value);
            if (Number.isInteger(n) && digits === 0) return String(n);
            return n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: digits });
        },

        // Row helpers below tolerate row === null because Alpine re-evaluates
        // x-show expressions in the brief window between setting `active = null`
        // and the surrounding x-if unmounting the popup's children. Defensive
        // null guards keep the console clean in that race.

        dimsLine(row) {
            if (!row) return '—';
            const d = this.unitsMode === 'metric' ? row.unit_metric : row.unit_imperial;
            if (!d) return '—';
            const all = [d.length, d.width, d.height];
            if (all.every((v) => v === null || v === undefined)) return '—';
            return all.map((v) => this.fmt(v, 1)).join(' × ');
        },

        weightFmt(row) {
            if (!row) return '—';
            const v = this.unitsMode === 'metric' ? row.unit_metric?.weight : row.unit_imperial?.weight;
            return this.fmt(v, this.unitsMode === 'metric' ? 3 : 2);
        },

        // Three big numbers for the popup hero: L, W, H. No units, just the
        // figures — units are shown by the toggle and the section header.
        heroDims(row, kind /* 'unit' | 'box' */) {
            const blank = [
                { label: 'Length', value: '—' },
                { label: 'Width',  value: '—' },
                { label: 'Height', value: '—' },
            ];
            if (!row) return blank;
            const src = row[`${kind}_${this.unitsMode}`] || {};
            return [
                { label: 'Length', value: this.fmt(src.length, 2) },
                { label: 'Width',  value: this.fmt(src.width,  2) },
                { label: 'Height', value: this.fmt(src.height, 2) },
            ];
        },

        // Weight + volume secondary line.
        heroExtras(row, kind /* 'unit' | 'box' */) {
            if (!row) return { weight: '—', volume: '—' };
            const src    = row[`${kind}_${this.unitsMode}`] || {};
            const metric = this.unitsMode === 'metric';
            const wUnit  = metric ? 'kg'   : 'lb';
            const vUnit  = metric ? 'cm³'  : 'in³';
            return {
                weight: this.fmt(src.weight, metric ? 3 : 2) + (src.weight !== null && src.weight !== undefined ? ' ' + wUnit : ''),
                volume: this.fmt(src.volume, 2)              + (src.volume !== null && src.volume !== undefined ? ' ' + vUnit : ''),
            };
        },

        dimensionsFor(row, kind /* 'unit' | 'box' */) {
            if (!row) return [];
            const m = row[`${kind}_metric`] || {};
            const i = row[`${kind}_imperial`] || {};
            const metric = this.unitsMode === 'metric';
            const lengthU = metric ? 'cm'  : 'in';
            const weightU = metric ? 'kg'  : 'lb';
            const volumeU = metric ? 'cm³' : 'in³';
            const src = metric ? m : i;
            return [
                { label: `Length (${lengthU})`, value: this.fmt(src.length, 2) },
                { label: `Width (${lengthU})`,  value: this.fmt(src.width,  2) },
                { label: `Height (${lengthU})`, value: this.fmt(src.height, 2) },
                { label: `Weight (${weightU})`, value: this.fmt(src.weight, metric ? 3 : 2) },
                { label: `Volume (${volumeU})`, value: this.fmt(src.volume, 2) },
            ];
        },

        hasBoxData(row) {
            if (!row) return false;
            const m = row.box_metric || {};
            return [m.length, m.width, m.height, m.weight, m.volume]
                .some((v) => v !== null && v !== undefined);
        },

        // ── Lightbox ─────────────────────────────────────────────────────────
        openLightbox(items, startIndex = 0) {
            const filtered = (items || []).filter((it) => it && it.url);
            if (filtered.length === 0) return;
            this.lightboxItems = filtered;
            this.lightboxIndex = Math.max(0, Math.min(startIndex, filtered.length - 1));
            this.lightboxResetZoom();
            this.lightboxOpen  = true;
            document.documentElement.style.overflow = 'hidden';
        },
        closeLightbox() {
            this.lightboxOpen = false;
            this.lightboxItems = [];
            this.lightboxIndex = 0;
            this.lightboxResetZoom();
            document.documentElement.style.overflow = '';
        },
        lightboxNext() {
            if (this.lightboxItems.length < 2) return;
            this.lightboxIndex = (this.lightboxIndex + 1) % this.lightboxItems.length;
            this.lightboxResetZoom();
        },
        lightboxPrev() {
            if (this.lightboxItems.length < 2) return;
            this.lightboxIndex = (this.lightboxIndex - 1 + this.lightboxItems.length) % this.lightboxItems.length;
            this.lightboxResetZoom();
        },
        lightboxSetZoom(z) {
            this.lightboxZoom = Math.max(1, Math.min(5, Math.round(z * 10) / 10));
            if (this.lightboxZoom === 1) { this.lightboxOffsetX = 0; this.lightboxOffsetY = 0; }
        },
        lightboxResetZoom() { this.lightboxZoom = 1; this.lightboxOffsetX = 0; this.lightboxOffsetY = 0; this.lightboxDrag = null; },
        lightboxToggleZoom() { this.lightboxSetZoom(this.lightboxZoom > 1 ? 1 : 2.5); },
        lightboxOnPointerDown(e) {
            if (this.lightboxZoom <= 1) return;
            this.lightboxDrag = { startX: e.clientX, startY: e.clientY, baseX: this.lightboxOffsetX, baseY: this.lightboxOffsetY };
            e.currentTarget.setPointerCapture?.(e.pointerId);
        },
        lightboxOnPointerMove(e) {
            if (!this.lightboxDrag) return;
            this.lightboxOffsetX = this.lightboxDrag.baseX + (e.clientX - this.lightboxDrag.startX);
            this.lightboxOffsetY = this.lightboxDrag.baseY + (e.clientY - this.lightboxDrag.startY);
        },
        lightboxOnPointerUp() { this.lightboxDrag = null; },
        lightboxOnWheel(e) { this.lightboxSetZoom(this.lightboxZoom + (e.deltaY < 0 ? 0.25 : -0.25)); },
        lightboxPhotosForActive() {
            if (!this.active) return [];
            const p = this.active.photos || {};
            return [
                { url: p.product,           label: 'Product photo' },
                { url: p.internal_barcode,  label: 'Internal barcode' },
                { url: p.gtin_barcode,      label: 'GTIN / EAN barcode' },
                { url: p.box,               label: 'Box photo' },
                { url: p.box_barcode,       label: 'Box barcode' },
            ].filter((it) => it.url);
        },

        // ── Pins / recents ───────────────────────────────────────────────────
        loadUserState() {
            try {
                const raw = localStorage.getItem(this.STORAGE_KEY);
                if (!raw) return;
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed.pins))    this.pins    = parsed.pins;
                if (Array.isArray(parsed.recents)) this.recents = parsed.recents;
            } catch (err) { console.warn('[decko] user state load failed:', err); }
        },
        saveUserState() {
            try {
                localStorage.setItem(this.STORAGE_KEY, JSON.stringify({ pins: this.pins, recents: this.recents }));
            } catch (err) { console.warn('[decko] user state save failed:', err); }
        },
        recordRecent(row) {
            if (!row) return;
            const entry = { sku: row.sku, description: row.description, code: row.category?.code || '', at: Date.now() };
            this.recents = [entry, ...this.recents.filter((r) => r.sku !== entry.sku)].slice(0, this.MAX_RECENTS);
            this.saveUserState();
        },
        togglePin(row) {
            if (!row) return;
            const exists = this.pins.find((p) => p.sku === row.sku);
            if (exists) this.pins = this.pins.filter((p) => p.sku !== row.sku);
            else        this.pins = [{ sku: row.sku, description: row.description, code: row.category?.code || '' }, ...this.pins];
            this.saveUserState();
        },
        isPinned(sku) { return this.pins.some((p) => p.sku === sku); },
        clearRecents() { this.recents = []; this.saveUserState(); },

        // ── Barcode scanner ──────────────────────────────────────────────────
        async openScanner() {
            this.scannerError = null;
            this.scannerHint  = 'Starting camera…';
            this.scannerOpen  = true;
            await new Promise((r) => requestAnimationFrame(r));
            try {
                if (typeof Html5Qrcode === 'undefined') throw new Error('Scanner library failed to load.');
                this._scanner = new Html5Qrcode('decko-scanner-target', false);
                await this._scanner.start(
                    { facingMode: 'environment' },
                    { fps: 12, qrbox: { width: 280, height: 140 }, aspectRatio: 1.5 },
                    (decoded) => this.handleScan(decoded),
                    () => {},
                );
                this.scannerHint = 'Hold steady — looking for a barcode.';
            } catch (err) {
                console.warn('[decko] scanner start failed:', err);
                this.scannerError = err?.message || String(err);
            }
        },
        async closeScanner() {
            if (this._scanner) {
                try { await this._scanner.stop(); } catch {}
                try { this._scanner.clear(); } catch {}
                this._scanner = null;
            }
            this.scannerOpen  = false;
            this.scannerError = null;
        },
        async handleScan(decoded) {
            const code = String(decoded).trim();
            if (!code) return;
            this.scannerHint = `Detected ${code}`;
            await this.closeScanner();
            try {
                const res = await fetch(`${DECKO.url}api/sku/${encodeURIComponent(code)}`, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' },
                });
                if (res.ok) {
                    const detail = await res.json();
                    this.query = '';
                    await this.fetchSkus();
                    this.openDetail(detail.sku);
                    return;
                }
            } catch {}
            this.query = code;
            await this.fetchSkus();
            if (this.results.length === 1) this.openDetail(this.results[0].sku);
        },

        // ── Edit mode (admin) ────────────────────────────────────────────────
        startEditing() {
            if (!this.canEdit || !this.active) return;
            const row = this.active;
            this.editForm = {
                description:      row.description       ?? '',
                variant_label:    row.variant           ?? '',
                units_per_box:    row.units_per_box     ?? '',
                internal_barcode: row.barcodes?.internal ?? '',
                gtin_barcode:     row.barcodes?.gtin     ?? '',
                box_barcode:      row.barcodes?.box      ?? '',
                unit_length_cm: row.unit_metric?.length  ?? '',
                unit_width_cm:  row.unit_metric?.width   ?? '',
                unit_height_cm: row.unit_metric?.height  ?? '',
                unit_weight_kg: row.unit_metric?.weight  ?? '',
                unit_volume_cm3: row.unit_metric?.volume ?? '',
                unit_length_in: row.unit_imperial?.length ?? '',
                unit_width_in:  row.unit_imperial?.width  ?? '',
                unit_height_in: row.unit_imperial?.height ?? '',
                unit_weight_lb: row.unit_imperial?.weight ?? '',
                unit_volume_in3: row.unit_imperial?.volume ?? '',
                box_length_cm: row.box_metric?.length    ?? '',
                box_width_cm:  row.box_metric?.width     ?? '',
                box_height_cm: row.box_metric?.height    ?? '',
                box_weight_kg: row.box_metric?.weight    ?? '',
                box_volume_cm3: row.box_metric?.volume   ?? '',
                box_length_in: row.box_imperial?.length  ?? '',
                box_width_in:  row.box_imperial?.width   ?? '',
                box_height_in: row.box_imperial?.height  ?? '',
                box_weight_lb: row.box_imperial?.weight  ?? '',
                box_volume_in3: row.box_imperial?.volume ?? '',
            };
            this.editErrors  = {};
            this.editMessage = null;
            this.editMode    = true;
        },
        cancelEditing() {
            this.editMode    = false;
            this.editForm    = {};
            this.editErrors  = {};
            this.editMessage = null;
        },
        async saveEditing() {
            if (!this.canEdit || !this.active) return;
            this.editSaving = true;
            this.editErrors = {};
            try {
                const res = await fetch(`${DECKO.url}api/sku/${encodeURIComponent(this.active.sku)}`, {
                    method:  'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type':  'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-Token':  DECKO.csrfToken,
                    },
                    body: JSON.stringify(this.editForm),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) {
                    if (res.status === 422 && data.fields) this.editErrors = data.fields;
                    else this.editMessage = { type: 'error', text: data.error || `Save failed (${res.status}).` };
                    return;
                }
                this.active      = data;
                this.editMode    = false;
                this.editMessage = { type: 'ok', text: 'Saved' };
                setTimeout(() => { this.editMessage = null; }, 2500);
                this.fetchSkus();
            } catch (err) {
                console.warn('[decko] save failed:', err);
                this.editMessage = { type: 'error', text: 'Network error — try again' };
            } finally {
                this.editSaving = false;
            }
        },
    };
};
