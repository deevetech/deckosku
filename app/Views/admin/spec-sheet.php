<?php
/** @var array<string,mixed> $sku */
use App\Core\View;

$fmt = static function (mixed $v, int $digits = 2): string {
    if ($v === null || $v === '') return '—';
    if (!is_numeric($v)) return (string) $v;
    return rtrim(rtrim(number_format((float) $v, $digits, '.', ''), '0'), '.');
};
$L = $sku['unit_length_cm'] ?? null;
$W = $sku['unit_width_cm']  ?? null;
$H = $sku['unit_height_cm'] ?? null;
?>
<div class="mx-auto max-w-[760px] px-6 py-8 print:px-0 print:py-0">

    <!-- Print toolbar (hidden when printing) -->
    <header class="no-print mb-6 flex items-center justify-between gap-4">
        <a href="<?= View::url('') ?>" class="inline-flex items-center gap-1.5 font-mono text-xs uppercase tracking-[0.16em] text-ink-soft hover:text-ink">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
            Back to dashboard
        </a>
        <button onclick="window.print()" class="btn-press inline-flex items-center gap-2 rounded-2xl bg-accent px-4 py-2.5 text-sm font-semibold text-white hover:bg-accent/90">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
            Print / Save PDF
        </button>
    </header>

    <!-- Sheet -->
    <article class="rounded-3xl border border-line bg-white p-8 shadow-card print:border-0 print:shadow-none print:p-0">

        <!-- Header -->
        <header class="flex items-start justify-between gap-6 border-b border-line pb-6">
            <div class="min-w-0">
                <p class="font-mono text-[11px] uppercase tracking-[0.18em] text-accent">
                    <?= View::e((string) ($sku['category_code'] ?? '')) ?> · <?= View::e((string) ($sku['category_name'] ?? '')) ?>
                </p>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight md:text-3xl"><?= View::e((string) ($sku['description'] ?? '')) ?></h1>
                <p class="mt-1 text-sm text-ink-soft"><?= View::e((string) ($sku['variant_label'] ?? '')) ?></p>
            </div>
            <div class="text-right">
                <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-ink-muted">SKU</p>
                <p class="mt-1 font-mono text-xl font-bold tabular"><?= View::e((string) $sku['sku_code']) ?></p>
            </div>
        </header>

        <!-- Photos + key data -->
        <section class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-[260px_1fr]">
            <div class="grid gap-3">
                <?php if (!empty($sku['photo_product'])): ?>
                    <div class="aspect-square overflow-hidden rounded-2xl border border-line bg-canvas">
                        <img src="<?= View::url('photos/' . $sku['photo_product']) ?>" alt="<?= View::e((string) ($sku['description'] ?? '')) ?>" class="h-full w-full object-contain bg-white">
                    </div>
                <?php endif; ?>
                <div class="grid grid-cols-2 gap-3">
                    <?php if (!empty($sku['photo_internal_barcode'])): ?>
                        <div class="rounded-xl border border-line bg-white p-2">
                            <p class="font-mono text-[9px] uppercase tracking-[0.14em] text-ink-muted">Internal</p>
                            <img src="<?= View::url('photos/' . $sku['photo_internal_barcode']) ?>" alt="" class="mt-1 h-16 w-full object-contain">
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($sku['photo_gtin_barcode'])): ?>
                        <div class="rounded-xl border border-line bg-white p-2">
                            <p class="font-mono text-[9px] uppercase tracking-[0.14em] text-ink-muted">GTIN</p>
                            <img src="<?= View::url('photos/' . $sku['photo_gtin_barcode']) ?>" alt="" class="mt-1 h-16 w-full object-contain">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="grid gap-4">
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">Identifiers</p>
                    <dl class="mt-2 divide-y divide-line-soft border-y border-line-soft">
                        <div class="flex items-baseline justify-between py-2"><dt class="text-sm text-ink-soft">Internal barcode</dt><dd class="font-mono text-sm font-medium tabular"><?= View::e((string) ($sku['internal_barcode'] ?? '—')) ?></dd></div>
                        <div class="flex items-baseline justify-between py-2"><dt class="text-sm text-ink-soft">GTIN / EAN</dt><dd class="font-mono text-sm font-medium tabular"><?= View::e((string) ($sku['gtin_barcode'] ?? '—')) ?></dd></div>
                        <div class="flex items-baseline justify-between py-2"><dt class="text-sm text-ink-soft">Box barcode</dt><dd class="font-mono text-sm font-medium tabular"><?= View::e((string) ($sku['box_barcode'] ?? '—')) ?></dd></div>
                        <div class="flex items-baseline justify-between py-2"><dt class="text-sm text-ink-soft">Units / box</dt><dd class="font-mono text-sm font-medium tabular"><?= $sku['units_per_box'] !== null ? (int) $sku['units_per_box'] : '—' ?></dd></div>
                    </dl>
                </div>

                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">Unit dimensions</p>
                    <table class="mt-2 w-full text-sm">
                        <thead>
                            <tr class="text-left font-mono text-[10px] uppercase tracking-[0.14em] text-ink-muted">
                                <th class="border-b border-line pb-1"></th>
                                <th class="border-b border-line pb-1 text-right">Metric</th>
                                <th class="border-b border-line pb-1 text-right">Imperial</th>
                            </tr>
                        </thead>
                        <tbody class="font-mono tabular text-sm">
                            <tr><td class="text-ink-soft py-1">Length</td><td class="text-right py-1"><?= $fmt($sku['unit_length_cm']) ?> cm</td><td class="text-right py-1"><?= $fmt($sku['unit_length_in']) ?> in</td></tr>
                            <tr><td class="text-ink-soft py-1">Width</td><td class="text-right py-1"><?= $fmt($sku['unit_width_cm']) ?> cm</td><td class="text-right py-1"><?= $fmt($sku['unit_width_in']) ?> in</td></tr>
                            <tr><td class="text-ink-soft py-1">Height</td><td class="text-right py-1"><?= $fmt($sku['unit_height_cm']) ?> cm</td><td class="text-right py-1"><?= $fmt($sku['unit_height_in']) ?> in</td></tr>
                            <tr><td class="text-ink-soft py-1">Weight</td><td class="text-right py-1"><?= $fmt($sku['unit_weight_kg'], 3) ?> kg</td><td class="text-right py-1"><?= $fmt($sku['unit_weight_lb']) ?> lb</td></tr>
                            <tr><td class="text-ink-soft py-1">Volume</td><td class="text-right py-1"><?= $fmt($sku['unit_volume_cm3']) ?> cm³</td><td class="text-right py-1"><?= $fmt($sku['unit_volume_in3']) ?> in³</td></tr>
                        </tbody>
                    </table>
                </div>

                <?php if ($sku['box_length_cm'] || $sku['box_width_cm'] || $sku['box_height_cm']): ?>
                <div>
                    <p class="font-mono text-[10px] uppercase tracking-[0.18em] text-ink-soft">Box dimensions</p>
                    <table class="mt-2 w-full text-sm">
                        <thead>
                            <tr class="text-left font-mono text-[10px] uppercase tracking-[0.14em] text-ink-muted">
                                <th class="border-b border-line pb-1"></th>
                                <th class="border-b border-line pb-1 text-right">Metric</th>
                                <th class="border-b border-line pb-1 text-right">Imperial</th>
                            </tr>
                        </thead>
                        <tbody class="font-mono tabular text-sm">
                            <tr><td class="text-ink-soft py-1">Length</td><td class="text-right py-1"><?= $fmt($sku['box_length_cm']) ?> cm</td><td class="text-right py-1"><?= $fmt($sku['box_length_in']) ?> in</td></tr>
                            <tr><td class="text-ink-soft py-1">Width</td><td class="text-right py-1"><?= $fmt($sku['box_width_cm']) ?> cm</td><td class="text-right py-1"><?= $fmt($sku['box_width_in']) ?> in</td></tr>
                            <tr><td class="text-ink-soft py-1">Height</td><td class="text-right py-1"><?= $fmt($sku['box_height_cm']) ?> cm</td><td class="text-right py-1"><?= $fmt($sku['box_height_in']) ?> in</td></tr>
                            <tr><td class="text-ink-soft py-1">Weight</td><td class="text-right py-1"><?= $fmt($sku['box_weight_kg'], 3) ?> kg</td><td class="text-right py-1"><?= $fmt($sku['box_weight_lb']) ?> lb</td></tr>
                            <tr><td class="text-ink-soft py-1">Volume</td><td class="text-right py-1"><?= $fmt($sku['box_volume_cm3']) ?> cm³</td><td class="text-right py-1"><?= $fmt($sku['box_volume_in3']) ?> in³</td></tr>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Footer -->
        <footer class="mt-8 flex items-center justify-between border-t border-line pt-4 text-xs text-ink-muted font-mono">
            <span>DECKO — DIY Home Improvement Products</span>
            <span>Generated <?= date('Y-m-d') ?></span>
        </footer>
    </article>
</div>
