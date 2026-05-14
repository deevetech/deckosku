<?php
/** @var array<string,mixed> $summary */
/** @var string $excelPath */
/** @var ?string $message */
/** @var ?string $errorMessage */
/** @var string $csrfToken */
use App\Core\View;
$lastImport = $summary['last_import'] ?? null;
?>
<div class="min-h-[100dvh]">
    <header class="border-b border-line bg-paper/80 backdrop-blur-md sticky top-0 z-20">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-6 py-4">
            <a href="<?= View::url('') ?>" class="inline-flex items-center gap-2 text-[13px] text-ink-soft hover:text-ink">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                Back to dashboard
            </a>
            <span class="font-mono text-[11px] text-ink-mute tabular">Total · <?= (int) $summary['total'] ?> SKUs</span>
        </div>
    </header>

    <main class="mx-auto max-w-3xl px-6 py-10">
        <p class="label">Admin · Excel sync</p>
        <h1 class="headline mt-3 text-[40px] md:text-[52px]">Refresh the catalogue.</h1>
        <p class="mt-4 max-w-xl text-[15px] text-ink-soft leading-relaxed">
            Upload a new .xlsx or re-run the import against the current one. Everything in the dashboard rebuilds — SKUs, dimensions, categories, and the 340 embedded product photos.
        </p>

        <?php if ($message): ?>
            <div role="status" class="mt-8 flex items-start gap-3 rounded-2xl border border-ember/30 bg-ember/[0.05] p-4 text-[13.5px]">
                <span class="font-mono text-ember">✓</span><span><?= View::e($message) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($errorMessage): ?>
            <div role="alert" class="mt-8 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-[13.5px] text-red-900">
                <span class="font-mono">✗</span><span><?= View::e($errorMessage) ?></span>
            </div>
        <?php endif; ?>

        <section class="mt-10 grid gap-4 rounded-2xl border border-line bg-surface p-6 shadow-card md:grid-cols-[1fr_auto] md:items-center md:p-8">
            <div>
                <p class="label !text-[10px]">Source workbook</p>
                <p class="mt-1.5 break-all font-mono text-[13px] tabular"><?= View::e($excelPath) ?></p>
                <?php if ($lastImport): ?>
                    <p class="mt-3 text-[12px] text-ink-soft tabular">
                        Last imported <span class="font-mono text-ink"><?= View::e((string) $lastImport['created_at']) ?></span> —
                        <span class="font-mono text-ink"><?= (int) $lastImport['rows_imported'] ?></span> SKUs,
                        <span class="font-mono text-ink"><?= (int) $lastImport['photos_saved'] ?></span> photos,
                        <span class="font-mono text-ink"><?= (int) $lastImport['duration_ms'] ?> ms</span>.
                    </p>
                <?php endif; ?>
            </div>
            <form method="post" action="<?= View::url('admin/reimport') ?>" class="self-center">
                <input type="hidden" name="_token" value="<?= View::e($csrfToken) ?>">
                <button type="submit" class="pill">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-2.64-6.36"/><path d="M21 4v6h-6"/></svg>
                    Re-run on current file
                </button>
            </form>
        </section>

        <section class="mt-6 rounded-2xl border border-dashed border-line bg-paper p-6 md:p-8">
            <p class="label !text-[10px]">Replace workbook</p>
            <h2 class="mt-1.5 text-[18px] font-medium">Upload a new .xlsx</h2>
            <p class="mt-1 text-[12.5px] text-ink-soft">
                The current file is backed up to <span class="font-mono text-ink">…/&lt;timestamp&gt;.bak</span> before being replaced. Import runs automatically after upload.
            </p>
            <form method="post" action="<?= View::url('admin/import') ?>" enctype="multipart/form-data" class="mt-5 grid gap-4 md:grid-cols-[1fr_auto] md:items-center">
                <input type="hidden" name="_token" value="<?= View::e($csrfToken) ?>">
                <label class="flex items-center gap-3 rounded-xl border border-line bg-surface px-4 py-3 text-[13px] cursor-pointer hover:bg-paper-soft transition-colors">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="text-ink-mute shrink-0"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                    <input type="file" name="workbook" accept=".xlsx" required class="flex-1 text-[12.5px] file:mr-3 file:rounded-lg file:border-0 file:bg-ink file:px-3 file:py-1.5 file:text-[11px] file:font-medium file:text-paper file:hover:bg-ink-soft">
                </label>
                <button type="submit" class="pill">Upload &amp; import</button>
            </form>
        </section>

        <p class="mt-8 text-[11.5px] font-mono text-ink-mute tabular">
            CLI alternative: <code class="text-ink-soft">php bin/import.php /path/to/workbook.xlsx</code>
        </p>
    </main>
</div>
