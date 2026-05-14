<?php
/** @var array<int,array<string,mixed>> $categories */
/** @var array<int,array<string,mixed>> $skus */
/** @var array<int,string> $selectedSkus */
/** @var string $query */
/** @var int $categoryId */
/** @var string $format */
use App\Core\View;
$selectedSet = array_flip($selectedSkus);
$formatDef = [
    '4x2'     => ['label' => '4 × 2 in · 10 per A4',  'w' => '4in',   'h' => '2in',   'cols' => 2, 'fontBase' => '11px'],
    '3.5x1.5' => ['label' => 'Avery 3.5 × 1.5 in',    'w' => '3.5in', 'h' => '1.5in', 'cols' => 2, 'fontBase' => '10px'],
    '4x6'     => ['label' => '4 × 6 in · shipping',   'w' => '4in',   'h' => '6in',   'cols' => 1, 'fontBase' => '13px'],
];
$fmt = $formatDef[$format] ?? $formatDef['4x2'];
?>
<style>
    @page { size: A4; margin: 12mm; }
    .label-sheet { display: grid; grid-template-columns: repeat(<?= (int) $fmt['cols'] ?>, <?= $fmt['w'] ?>); gap: 4mm; justify-content: center; }
    .label-card  { width: <?= $fmt['w'] ?>; height: <?= $fmt['h'] ?>; padding: 4mm; border: 1px solid #d6d2c5; background: #fff; color: #18171c; font-size: <?= $fmt['fontBase'] ?>; page-break-inside: avoid; overflow: hidden; display: grid; grid-template-rows: auto 1fr auto; gap: 2mm; font-family: "Geist", system-ui, sans-serif; border-radius: 4px; }
    .label-card .barcode-img { max-height: 18mm; max-width: 100%; object-fit: contain; margin-inline: auto; }
    @media print {
        html, body { background: #fff !important; color: #000 !important; }
        body::before { display: none !important; }
        .no-print { display: none !important; }
        .print-sheet { padding: 0 !important; background: #fff !important; }
    }
</style>

<div x-data="labelsApp()" class="min-h-[100dvh]">

    <header class="no-print border-b border-line bg-paper/80 backdrop-blur-md sticky top-0 z-20">
        <div class="mx-auto flex flex-wrap items-center justify-between gap-3 px-6 py-4 lg:px-10">
            <a href="<?= View::url('') ?>" class="inline-flex items-center gap-2 text-[13px] text-ink-soft hover:text-ink">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                Back
            </a>
            <form method="get" action="<?= View::url('admin/labels') ?>" class="flex flex-wrap items-center gap-2">
                <input type="search" name="q" value="<?= View::e($query) ?>" placeholder="Filter SKUs…"
                       class="rounded-full border border-line bg-surface px-3.5 py-1.5 text-[12.5px] font-mono tabular placeholder:text-ink-dim focus:border-ink focus:outline-none focus:ring-0 w-44">
                <select name="c" class="rounded-full border border-line bg-surface px-3 py-1.5 text-[12.5px] focus:border-ink focus:outline-none focus:ring-0">
                    <option value="0">All categories</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= $categoryId === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= View::e((string) $c['code']) ?> · <?= View::e((string) $c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="format" class="rounded-full border border-line bg-surface px-3 py-1.5 text-[12.5px] focus:border-ink focus:outline-none focus:ring-0">
                    <?php foreach ($formatDef as $key => $def): ?>
                        <option value="<?= View::e($key) ?>" <?= $format === $key ? 'selected' : '' ?>><?= View::e($def['label']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="pill ghost py-1.5 px-3.5 text-[12.5px]">Apply</button>
            </form>
            <button @click="window.print()" :disabled="selected.length === 0" class="pill disabled:opacity-50">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print <span class="tabular font-mono opacity-80">·</span> <span class="tabular font-mono" x-text="selected.length"></span>
            </button>
        </div>

        <div class="border-t border-line-soft px-6 lg:px-10 py-2.5 text-[11.5px] font-mono text-ink-mute tabular flex items-center justify-between">
            <span><?= count($skus) ?> result<?= count($skus) === 1 ? '' : 's' ?> · <span class="text-ink" x-text="selected.length"></span> selected</span>
            <div class="flex items-center gap-3">
                <button @click="selectAll()" class="btn-press hover:text-ink">Select all</button>
                <button @click="selectNone()" class="btn-press hover:text-ink">Clear</button>
            </div>
        </div>
    </header>

    <main class="no-print mx-auto max-w-7xl px-6 py-8 lg:px-10">
        <p class="label !text-[10px] mb-3">Pick SKUs</p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
            <?php foreach ($skus as $r): $skuJs = htmlspecialchars(json_encode((string) $r['sku_code']), ENT_QUOTES); ?>
                <button type="button"
                    @click="toggle(<?= $skuJs ?>)"
                    :class="selected.includes(<?= $skuJs ?>) ? 'border-ink bg-paper-soft' : 'border-line bg-surface hover:bg-paper-soft hover:border-line-strong'"
                    class="btn-press flex items-center gap-2.5 rounded-xl border px-3 py-2.5 text-left transition-colors">
                    <span class="font-mono text-[11px] text-ember tabular shrink-0" x-text="selected.includes(<?= $skuJs ?>) ? '●' : '○'"></span>
                    <span class="min-w-0">
                        <span class="block truncate text-[13px] font-mono font-semibold tabular"><?= View::e((string) $r['sku_code']) ?></span>
                        <span class="block truncate text-[11.5px] text-ink-mute"><?= View::e((string) ($r['description'] ?? '')) ?></span>
                    </span>
                </button>
            <?php endforeach; ?>
        </div>
    </main>

    <section class="print-sheet mx-auto max-w-7xl px-6 lg:px-10 pb-12">
        <p class="no-print label !text-[10px] mt-4 mb-3">Preview · only ticked SKUs will print</p>
        <div class="label-sheet mx-auto bg-white p-4 rounded-2xl border border-line">
            <?php foreach ($skus as $r): $skuJs = htmlspecialchars(json_encode((string) $r['sku_code']), ENT_QUOTES); ?>
                <div class="label-card" x-show="selected.includes(<?= $skuJs ?>)" x-cloak>
                    <header style="display:flex; align-items:flex-start; justify-content:space-between; gap:4mm;">
                        <div style="min-width:0;">
                            <p style="font-family:'JetBrains Mono', monospace; font-size:9px; letter-spacing:0.16em; text-transform:uppercase; color:#6b6873;"><?= View::e((string) ($r['category_code'] ?? '')) ?></p>
                            <p style="font-family:'JetBrains Mono', monospace; font-weight:700; font-size:14px; margin-top:1mm; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= View::e((string) $r['sku_code']) ?></p>
                        </div>
                        <?php if (!empty($r['photo_product'])): ?>
                            <img src="<?= View::url('photos/' . $r['photo_product']) ?>" alt="" style="height:14mm; width:14mm; object-fit:contain; border:1px solid #e8e2d6; border-radius:3px;">
                        <?php endif; ?>
                    </header>
                    <div style="min-height:0;">
                        <p style="font-size:11px; line-height:1.25; color:#18171c; overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; line-clamp:2; -webkit-box-orient:vertical;">
                            <?= View::e((string) ($r['description'] ?? '')) ?>
                        </p>
                        <p style="font-family:'JetBrains Mono', monospace; font-size:10px; color:#43424a; margin-top:1mm;">
                            <?php $L=$r['unit_length_cm']; $W=$r['unit_width_cm']; $H=$r['unit_height_cm']; ?>
                            <?php if ($L || $W || $H): ?>
                                <?= rtrim(rtrim((string)$L,'0'),'.') ?> × <?= rtrim(rtrim((string)$W,'0'),'.') ?> × <?= rtrim(rtrim((string)$H,'0'),'.') ?> cm
                            <?php endif; ?>
                            <?php if ($r['unit_weight_kg']): ?> · <?= rtrim(rtrim((string)$r['unit_weight_kg'],'0'),'.') ?> kg<?php endif; ?>
                            <?php if ($r['units_per_box']): ?> · <?= (int) $r['units_per_box'] ?>/box<?php endif; ?>
                        </p>
                    </div>
                    <?php if (!empty($r['photo_gtin_barcode'])): ?>
                        <img src="<?= View::url('photos/' . $r['photo_gtin_barcode']) ?>" alt="" class="barcode-img">
                    <?php elseif (!empty($r['photo_internal_barcode'])): ?>
                        <img src="<?= View::url('photos/' . $r['photo_internal_barcode']) ?>" alt="" class="barcode-img">
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<script>
window.labelsApp = function () {
    return {
        selected: <?= json_encode(array_values($selectedSkus)) ?>,
        allCodes: <?= json_encode(array_map(static fn ($r) => (string) $r['sku_code'], $skus)) ?>,
        toggle(code) {
            const i = this.selected.indexOf(code);
            if (i === -1) this.selected.push(code);
            else this.selected.splice(i, 1);
        },
        selectAll() { this.selected = [...new Set([...this.selected, ...this.allCodes])]; },
        selectNone() { this.selected = []; },
    };
};
</script>
