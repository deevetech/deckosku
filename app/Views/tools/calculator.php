<?php
/** @var array<int,array<string,mixed>> $skus */
use App\Core\View;
?>
<div x-data="calculator()" class="min-h-[100dvh]">
    <header class="border-b border-line bg-paper/80 backdrop-blur-md sticky top-0 z-20">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-4 lg:px-10">
            <a href="<?= View::url('') ?>" class="inline-flex items-center gap-2 text-[13px] text-ink-soft hover:text-ink">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                Back to dashboard
            </a>
            <span class="font-mono text-[11px] text-ink-mute uppercase tracking-[0.14em]">Tools · Logistics</span>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-10 lg:px-10">
        <p class="label">Container fit estimator</p>
        <h1 class="headline mt-3 text-[40px] md:text-[52px]">How many fit in a container?</h1>
        <p class="mt-4 max-w-2xl text-[15px] text-ink-soft leading-relaxed">
            Pick a SKU and a container. The estimator tries axis-aligned and 90°-rotated packing per layer, then picks the better result. For mixed-SKU loads or odd-shape items, defer to your freight forwarder.
        </p>

        <section class="mt-10 grid gap-5 rounded-2xl border border-line bg-surface p-6 shadow-card md:grid-cols-2 md:p-8">
            <label class="grid gap-1.5">
                <span class="text-[12.5px] font-medium">SKU</span>
                <select x-model="skuCode" class="w-full rounded-xl border border-line bg-surface px-4 py-3 pr-10 text-[13.5px] font-mono tabular focus:border-ink focus:outline-none focus:ring-0">
                    <option value="">— pick a SKU —</option>
                    <template x-for="s in skus" :key="s.sku">
                        <option :value="s.sku" x-text="s.sku + ' · ' + (s.name || '')"></option>
                    </template>
                </select>
            </label>
            <label class="grid gap-1.5">
                <span class="text-[12.5px] font-medium">Container / pallet</span>
                <select x-model="containerKey" class="w-full rounded-xl border border-line bg-surface px-4 py-3 pr-10 text-[13.5px] focus:border-ink focus:outline-none focus:ring-0">
                    <template x-for="(c, key) in containers" :key="key">
                        <option :value="key" x-text="c.label"></option>
                    </template>
                </select>
            </label>
            <details class="md:col-span-2 rounded-xl border border-dashed border-line bg-paper px-4 py-3 text-[12.5px]">
                <summary class="cursor-pointer label !text-[10px]">Custom container (cm)</summary>
                <div class="mt-3 grid grid-cols-3 gap-3">
                    <label class="grid gap-1"><span class="text-[10.5px] text-ink-mute">Length</span>
                        <input type="number" step="1" x-model.number="custom.L" class="rounded-lg border border-line bg-surface px-3 py-2 text-[13px] font-mono tabular focus:border-ink focus:outline-none focus:ring-0">
                    </label>
                    <label class="grid gap-1"><span class="text-[10.5px] text-ink-mute">Width</span>
                        <input type="number" step="1" x-model.number="custom.W" class="rounded-lg border border-line bg-surface px-3 py-2 text-[13px] font-mono tabular focus:border-ink focus:outline-none focus:ring-0">
                    </label>
                    <label class="grid gap-1"><span class="text-[10.5px] text-ink-mute">Height</span>
                        <input type="number" step="1" x-model.number="custom.H" class="rounded-lg border border-line bg-surface px-3 py-2 text-[13px] font-mono tabular focus:border-ink focus:outline-none focus:ring-0">
                    </label>
                </div>
                <p class="mt-2 text-[10.5px] text-ink-mute">Fill all three, then set container above to "Custom".</p>
            </details>
        </section>

        <section x-show="result" x-cloak class="mt-8 grid gap-6 md:grid-cols-[1fr_360px]">
            <div class="rounded-2xl border border-line bg-surface p-7 shadow-card">
                <p class="label !text-[10px]">Fits</p>
                <div class="mt-2 flex items-baseline gap-3">
                    <p class="font-mono text-5xl font-medium tabular tracking-tightest" x-text="result?.boxes ?? '—'"></p>
                    <span class="text-[15px] text-ink-soft">boxes</span>
                </div>
                <p class="mt-1 font-mono text-[13.5px] tabular text-ink-soft">
                    <span x-text="result?.units ?? '—'"></span> total units
                    <span x-show="selectedSku?.unitsPerBox" x-cloak class="text-ink-mute">(<span x-text="selectedSku?.unitsPerBox"></span>/box)</span>
                </p>
                <dl class="mt-6 divide-y divide-line-soft border-y border-line-soft">
                    <div class="flex items-baseline justify-between py-2.5"><dt class="text-[13px] text-ink-soft">Layer arrangement</dt><dd class="font-mono tabular text-[13px]" x-text="result ? `${result.perRow} × ${result.perCol}` : '—'"></dd></div>
                    <div class="flex items-baseline justify-between py-2.5"><dt class="text-[13px] text-ink-soft">Boxes / layer</dt><dd class="font-mono tabular text-[13px] font-medium" x-text="result ? (result.perRow * result.perCol) : '—'"></dd></div>
                    <div class="flex items-baseline justify-between py-2.5"><dt class="text-[13px] text-ink-soft">Layers stacked</dt><dd class="font-mono tabular text-[13px] font-medium" x-text="result?.layers ?? '—'"></dd></div>
                    <div class="flex items-baseline justify-between py-2.5"><dt class="text-[13px] text-ink-soft">Total weight</dt><dd class="font-mono tabular text-[13px]" x-text="result?.weight !== null && result?.weight !== undefined ? result.weight.toFixed(1) + ' kg' : '—'"></dd></div>
                    <div class="flex items-baseline justify-between py-2.5"><dt class="text-[13px] text-ink-soft">Floor utilisation</dt><dd class="font-mono tabular text-[13px]" x-text="result ? result.floorUse.toFixed(0) + '%' : '—'"></dd></div>
                    <div class="flex items-baseline justify-between py-2.5"><dt class="text-[13px] text-ink-soft">Volume utilisation</dt><dd class="font-mono tabular text-[13px]" x-text="result ? result.volumeUse.toFixed(0) + '%' : '—'"></dd></div>
                </dl>
                <p class="mt-4 text-[11.5px] text-ink-mute leading-snug">
                    Estimate only. Real loading depends on bracing, fragility, and orientation.
                </p>
            </div>

            <div class="rounded-2xl border border-line bg-surface p-7 shadow-card">
                <p class="label !text-[10px]">Top-down view</p>
                <p class="mt-1 font-mono tabular text-[11.5px] text-ink-mute" x-text="result ? `${result.container.L} × ${result.container.W} cm` : ''"></p>
                <div class="mt-4 aspect-[3/2] w-full overflow-hidden rounded-xl border border-line bg-paper-soft">
                    <svg :viewBox="`0 0 ${result?.container.L ?? 100} ${result?.container.W ?? 100}`" preserveAspectRatio="xMidYMid meet" class="h-full w-full"
                         x-effect="$el.innerHTML = svgMarkup"></svg>
                </div>
            </div>
        </section>

        <p x-show="!result" x-cloak class="mt-10 text-[13.5px] text-ink-soft">Pick a SKU with box dimensions to see capacity.</p>
    </main>
</div>

<script>
window.calculator = function () {
    const containers = {
        '20ft':    { label: "20ft ISO container",         L: 589.8,  W: 235.2, H: 239.3 },
        '40ft':    { label: "40ft ISO container",         L: 1203.2, W: 235.2, H: 239.3 },
        '40ftHC':  { label: "40ft High-Cube container",   L: 1203.2, W: 235.2, H: 269.8 },
        'euroPal': { label: "Euro pallet (1.2 × 0.8 m)",  L: 120,    W: 80,    H: 220 },
        'usPal':   { label: "US pallet (1.2 × 1.0 m)",    L: 120,    W: 100,   H: 220 },
        'custom':  { label: "Custom (set below)",         L: 0,      W: 0,     H: 0 },
    };
    return {
        skus: <?= json_encode($skus) ?>,
        skuCode: '',
        containerKey: '20ft',
        custom: { L: null, W: null, H: null },
        containers,
        get selectedSku() { return this.skus.find((s) => s.sku === this.skuCode) || null; },
        get hasBox() { const s = this.selectedSku; return !!(s && s.box.L > 0 && s.box.W > 0 && s.box.H > 0); },
        get activeContainer() {
            if (this.containerKey === 'custom' && this.custom.L && this.custom.W && this.custom.H) return { L: this.custom.L, W: this.custom.W, H: this.custom.H };
            return containers[this.containerKey];
        },
        get result() {
            if (!this.hasBox) return null;
            const c = this.activeContainer;
            if (!c || !c.L || !c.W || !c.H) return null;
            const b = this.selectedSku.box;
            const a = { perRow: Math.floor(c.L / b.L), perCol: Math.floor(c.W / b.W), bw: b.L, bh: b.W };
            const r = { perRow: Math.floor(c.L / b.W), perCol: Math.floor(c.W / b.L), bw: b.W, bh: b.L };
            const winner = (a.perRow * a.perCol) >= (r.perRow * r.perCol) ? a : r;
            const layers = Math.max(0, Math.floor(c.H / b.H));
            const perLayer = winner.perRow * winner.perCol;
            const boxes = perLayer * layers;
            const units = this.selectedSku.unitsPerBox ? boxes * this.selectedSku.unitsPerBox : boxes;
            const weight = b.kg ? boxes * b.kg : null;
            const floorUse  = perLayer > 0 ? (perLayer * winner.bw * winner.bh) / (c.L * c.W) * 100 : 0;
            const volumeUse = boxes > 0 ? (boxes * b.L * b.W * b.H) / (c.L * c.W * c.H) * 100 : 0;
            const layout = [];
            for (let row = 0; row < winner.perCol; row++) {
                for (let col = 0; col < winner.perRow; col++) {
                    layout.push({ x: col * winner.bw, y: row * winner.bh, w: winner.bw, h: winner.bh });
                }
            }
            return { boxes, units, weight, layers, perRow: winner.perRow, perCol: winner.perCol, floorUse, volumeUse, container: c, layout };
        },
        get svgMarkup() {
            const r = this.result;
            if (!r) return '';
            let html = `<rect width="${r.container.L}" height="${r.container.W}" fill="#fefcf8" stroke="#e8e2d6" stroke-width="2"/>`;
            for (const box of r.layout) {
                html += `<rect x="${box.x}" y="${box.y}" width="${box.w}" height="${box.h}" fill="#f4d8c3" stroke="#c2410c" stroke-width="0.6" stroke-linejoin="round"/>`;
            }
            return html;
        },
    };
};
</script>
