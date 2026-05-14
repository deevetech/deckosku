<?php
/** @var array<string,mixed> $summary */
/** @var array<int,array<string,mixed>> $categories */
/** @var array<string,mixed>|null $user */
/** @var bool $isAdmin */
/** @var string $csrfToken */
use App\Core\View;

$lastImport = $summary['last_import'] ?? null;
?>
<div x-data="dashboard()" class="min-h-[100dvh] flex flex-col">

    <!-- ───────── Topbar — frosted, ink-driven, never crowded ───────── -->
    <header class="sticky top-0 z-30 border-b border-line/80 bg-paper/80 backdrop-blur-md">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-3.5 lg:px-10">
            <a href="<?= View::url('') ?>" class="flex items-center gap-3 group">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-ink text-paper font-mono text-[13px] font-semibold tracking-tightest">D</span>
                <span class="leading-tight">
                    <span class="block text-[14px] font-medium tracking-tightest">DECKO</span>
                    <span class="block font-mono text-[10px] uppercase tracking-[0.16em] text-ink-mute">Size catalogue</span>
                </span>
            </a>

            <nav class="hidden md:flex items-center gap-1 text-[13px]">
                <button @click="bookmarksOpen = true" class="btn-press rounded-full px-3.5 py-1.5 text-ink-soft hover:text-ink hover:bg-paper-soft">Browse</button>
                <a href="<?= View::url('tools/calculator') ?>" class="btn-press rounded-full px-3.5 py-1.5 text-ink-soft hover:text-ink hover:bg-paper-soft">Calculator</a>
                <?php if (!empty($isAdmin)): ?>
                    <a href="<?= View::url('admin/labels') ?>" class="btn-press rounded-full px-3.5 py-1.5 text-ink-soft hover:text-ink hover:bg-paper-soft">Labels</a>
                    <a href="<?= View::url('admin/users') ?>" class="btn-press rounded-full px-3.5 py-1.5 text-ink-soft hover:text-ink hover:bg-paper-soft">Team</a>
                    <a href="<?= View::url('admin/import') ?>" class="btn-press rounded-full px-3.5 py-1.5 text-ink-soft hover:text-ink hover:bg-paper-soft">Import</a>
                <?php endif; ?>
            </nav>

            <div class="flex items-center gap-3">
                <span class="hidden sm:flex items-center gap-2 text-[11px] font-mono text-ink-mute">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-ember status-pulse"></span>
                    <span class="uppercase tracking-[0.14em]"><?= !empty($isAdmin) ? 'Admin' : 'Viewer' ?></span>
                </span>
                <details class="relative">
                    <summary class="btn-press list-none cursor-pointer flex h-8 w-8 items-center justify-center rounded-full bg-paper-soft border border-line text-[11px] font-medium hover:bg-paper-deep">
                        <?= View::e(strtoupper(substr((string) ($user['name'] ?? 'A'), 0, 1))) ?>
                    </summary>
                    <div class="absolute right-0 mt-2 w-56 rounded-2xl border border-line bg-surface shadow-pop p-2 text-[13px]">
                        <p class="px-3 py-2 text-ink-soft truncate"><?= View::e((string) ($user['email'] ?? '')) ?></p>
                        <hr class="my-1 border-line-soft">
                        <a href="<?= View::url('me/password') ?>" class="block rounded-lg px-3 py-2 hover:bg-paper-soft">Change password</a>
                        <form method="post" action="<?= View::url('logout') ?>">
                            <input type="hidden" name="_token" value="<?= View::e($csrfToken) ?>">
                            <button class="block w-full text-left rounded-lg px-3 py-2 hover:bg-paper-soft text-ink-soft">Sign out</button>
                        </form>
                    </div>
                </details>
            </div>
        </div>
    </header>

    <!-- ───────── Hero search ───────── -->
    <section class="border-b border-line">
        <div class="mx-auto max-w-5xl px-6 pt-14 pb-8 lg:pt-20 lg:pb-10">
            <p class="label">Warehouse catalogue · <?= (int) $summary['total'] ?> SKUs · 7 categories</p>
            <h1 class="headline mt-4 text-[44px] md:text-[58px] tracking-tightest">
                Type a SKU,<br>or anything close.
            </h1>
            <p class="mt-3 text-[15px] text-ink-soft max-w-md leading-relaxed">
                Live search across SKU codes, GTIN/EAN, internal barcodes and product names — pulls dimensions and photos straight from the master workbook.
            </p>

            <!-- The hero search bar with live typeahead. -->
            <div class="relative mt-8" @click.outside="suggestionsOpen = false">
                <div class="flex items-center gap-3 rounded-2xl border border-line bg-surface px-5 py-4 shadow-card transition-shadow focus-within:shadow-pop focus-within:border-ink/30">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="text-ink-mute shrink-0">
                        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input
                        x-ref="search"
                        x-model="query"
                        @input.debounce.140ms="onSearchInput()"
                        @focus="suggestionsOpen = !!query"
                        @keydown="onSearchKey($event)"
                        type="search"
                        spellcheck="false"
                        autocomplete="off"
                        placeholder="Try DD-CRE-BL, 599957, ramp edge…"
                        class="flex-1 border-0 bg-transparent text-[18px] font-mono tabular placeholder:text-ink-dim focus:outline-none focus:ring-0">
                    <button @click="openScanner()" class="btn-press hidden sm:inline-flex items-center gap-1.5 rounded-full border border-line px-3 py-1.5 text-[12px] text-ink-soft hover:bg-paper-soft" title="Scan barcode">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7V5a2 2 0 0 1 2-2h2"/><path d="M21 7V5a2 2 0 0 0-2-2h-2"/><path d="M3 17v2a2 2 0 0 0 2 2h2"/><path d="M21 17v2a2 2 0 0 1-2 2h-2"/><path d="M5 8v8"/><path d="M9 8v8"/><path d="M13 8v8"/><path d="M17 8v8"/></svg>
                        Scan
                    </button>
                    <kbd class="hidden md:inline-flex items-center gap-1 rounded-md border border-line bg-paper-soft px-1.5 py-0.5 font-mono text-[10px] text-ink-mute">/</kbd>
                </div>

                <!-- Typeahead dropdown — sizes-first, no thumbnails. -->
                <div x-show="suggestionsOpen && results.length > 0" x-cloak
                     class="typeahead slide-in"
                     @click.outside="suggestionsOpen = false">
                    <template x-for="(row, idx) in results.slice(0, 8)" :key="row.sku">
                        <div class="typeahead-row"
                             :class="cursorIndex === idx ? 'is-cursor' : ''"
                             @mouseenter="cursorIndex = idx"
                             @click="openDetailFromSuggestion(row.sku, idx)">
                            <div class="min-w-0">
                                <p class="flex items-center gap-2">
                                    <span class="font-mono text-[10.5px] font-semibold text-ember tracking-tight tabular shrink-0" x-text="row.category.code"></span>
                                    <span class="font-mono text-[14px] font-semibold tracking-tight tabular truncate" x-text="row.sku"></span>
                                </p>
                                <p class="truncate text-[12.5px] text-ink-soft mt-0.5" x-text="row.description"></p>
                            </div>
                            <p class="font-mono tabular text-[14px] font-medium text-ink whitespace-nowrap hidden sm:block"
                               x-text="dimsLine(row) + (unitsMode === 'metric' ? ' cm' : ' in')"></p>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="text-ink-dim shrink-0"><path d="M9 6l6 6-6 6"/></svg>
                        </div>
                    </template>

                    <template x-if="results.length > 8">
                        <div class="px-4 py-2 border-t border-line-soft text-[11px] font-mono text-ink-mute tabular">
                            + <span x-text="results.length - 8"></span> more — see full results below
                        </div>
                    </template>
                </div>

                <!-- Empty state inside the dropdown when query has no matches. -->
                <div x-show="suggestionsOpen && query && results.length === 0 && !isLoading" x-cloak class="typeahead slide-in">
                    <div class="px-4 py-5 text-[13px] text-ink-soft">
                        <p>No SKU matches <span class="font-mono text-ink">"<span x-text="query"></span>"</span>.</p>
                        <p class="mt-1 text-[11px] text-ink-mute font-mono tabular">Try a shorter query, or a single SKU code fragment.</p>
                    </div>
                </div>
            </div>

            <!-- Quick filters / shortcuts under the search. -->
            <div class="mt-5 flex flex-wrap items-center gap-2 text-[12px]">
                <span class="label !text-[10px] mr-1">Filter by</span>
                <button @click="setCategory(0)"
                        :class="categoryId === 0 ? 'bg-ink text-paper' : 'bg-surface text-ink-soft hover:bg-paper-soft border border-line'"
                        class="btn-press rounded-full px-3 py-1.5 transition-colors">
                    All <span class="ml-1 font-mono tabular text-[11px] opacity-70"><?= (int) $summary['total'] ?></span>
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button @click="setCategory(<?= (int) $cat['id'] ?>)"
                            :class="categoryId === <?= (int) $cat['id'] ?> ? 'bg-ink text-paper' : 'bg-surface text-ink-soft hover:bg-paper-soft border border-line'"
                            class="btn-press rounded-full px-3 py-1.5 transition-colors">
                        <span class="font-mono font-semibold text-[11px] mr-1.5" :class="categoryId === <?= (int) $cat['id'] ?> ? 'text-ember-soft' : 'text-ember'"><?= View::e((string) $cat['code']) ?></span>
                        <?= View::e((string) $cat['name']) ?>
                        <span class="ml-1 font-mono tabular text-[11px] opacity-70"><?= (int) $cat['sku_count'] ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ───────── Results ───────── -->
    <section class="flex-1">
        <div class="mx-auto max-w-[1800px] px-4 lg:px-8">

            <!-- Sub-header for the result list -->
            <div id="decko-results-top" class="flex items-center justify-between border-b border-line-soft py-4">
                <p class="text-[12px] font-mono text-ink-mute tabular">
                    <template x-if="totalCount > 0">
                        <span>
                            <span class="text-ink" x-text="pageStart"></span>–<span class="text-ink" x-text="pageEnd"></span>
                            of <span class="text-ink" x-text="totalCount"></span>
                        </span>
                    </template>
                    <template x-if="totalCount === 0">
                        <span class="text-ink" x-text="resultsCount()"></span>
                    </template>
                    <span> result<span x-text="totalCount === 1 ? '' : 's'"></span></span>
                    <template x-if="categoryFilter">
                        <span> · category <span class="text-ember" x-text="categoryFilter"></span></span>
                    </template>
                    <template x-if="query">
                        <span> · <span class="text-ink">"<span x-text="query"></span>"</span></span>
                    </template>
                </p>
                <div class="flex items-center gap-2">
                    <div class="flex items-center rounded-full border border-line bg-surface p-0.5 text-[11px] font-mono tabular">
                        <button @click="unitsMode='metric'" :class="unitsMode==='metric' ? 'bg-ink text-paper' : 'text-ink-soft hover:text-ink'" class="btn-press rounded-full px-3 py-1 uppercase tracking-[0.1em]">cm·kg</button>
                        <button @click="unitsMode='imperial'" :class="unitsMode==='imperial' ? 'bg-ink text-paper' : 'text-ink-soft hover:text-ink'" class="btn-press rounded-full px-3 py-1 uppercase tracking-[0.1em]">in·lb</button>
                    </div>
                    <div class="flex items-center rounded-full border border-line bg-surface p-0.5 text-[11px] font-mono tabular">
                        <template x-for="size in [10, 50, 100, 'All']" :key="size">
                            <button @click="setPageSize(size)"
                                    :class="(pageSize === size || (size === 'All' && pageSize >= results.length && results.length > 0)) ? 'bg-ink text-paper' : 'text-ink-soft hover:text-ink'"
                                    class="btn-press rounded-full px-2.5 py-1 uppercase tracking-[0.1em]"
                                    x-text="size"></button>
                        </template>
                    </div>
                    <a :href="csvUrl()" class="btn-press inline-flex items-center gap-1.5 rounded-full border border-line bg-surface px-3 py-1.5 text-[12px] text-ink-soft hover:bg-paper-soft">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
                        CSV
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto -mx-4 lg:-mx-8 px-4 lg:px-8">
            <table class="lux">
                <thead>
                    <tr>
                        <th class="w-[200px]">SKU</th>
                        <th>Product</th>
                        <th class="num w-[140px]" x-text="unitsMode === 'metric' ? 'L × W × H (cm)' : 'L × W × H (in)'"></th>
                        <th class="num w-[80px]" x-text="unitsMode === 'metric' ? 'Weight (kg)' : 'Weight (lb)'"></th>
                        <th class="num w-[60px] hidden md:table-cell">U/Box</th>
                        <th class="w-[160px] hidden lg:table-cell">EAN</th>
                    </tr>
                </thead>
                <tbody class="rise">
                    <!-- Loading skeleton -->
                    <template x-if="isLoading && results.length === 0">
                        <template x-for="i in 8" :key="i">
                            <tr>
                                <td><span class="skeleton inline-block h-3 w-24">·</span></td>
                                <td><span class="skeleton inline-block h-3 w-56">·</span></td>
                                <td class="num"><span class="skeleton inline-block h-3 w-24">·</span></td>
                                <td class="num"><span class="skeleton inline-block h-3 w-12">·</span></td>
                                <td class="num hidden md:table-cell"><span class="skeleton inline-block h-3 w-10">·</span></td>
                                <td class="hidden lg:table-cell"><span class="skeleton inline-block h-3 w-28">·</span></td>
                            </tr>
                        </template>
                    </template>

                    <!-- Rows (paginated to pageSize) -->
                    <template x-for="(row, idx) in pagedResults" :key="row.sku">
                        <tr :class="active && active.sku === row.sku ? 'is-active' : ''"
                            :style="`--i: ${idx}`"
                            @click="openDetail(row.sku)">
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-2.5 whitespace-nowrap">
                                    <span class="font-mono text-[10px] font-semibold text-ember tracking-tight tabular shrink-0" x-text="row.category.code"></span>
                                    <span class="font-mono text-[13.5px] font-semibold tabular tracking-tight whitespace-nowrap" x-text="row.sku"></span>
                                </div>
                            </td>
                            <td>
                                <p class="text-[13.5px] truncate" x-text="row.description"></p>
                                <p class="text-[11.5px] text-ink-mute truncate" x-text="row.variant"></p>
                            </td>
                            <td class="num font-mono tabular text-[13px] text-ink-soft" x-text="dimsLine(row)"></td>
                            <td class="num font-mono tabular text-[13px] text-ink-soft" x-text="weightFmt(row)"></td>
                            <td class="num font-mono tabular text-[13px] text-ink-mute hidden md:table-cell" x-text="row.units_per_box ?? '—'"></td>
                            <td class="font-mono tabular text-[12px] text-ink-mute hidden lg:table-cell truncate" x-text="row.barcodes.gtin || row.barcodes.internal || '—'"></td>
                        </tr>
                    </template>

                    <!-- Empty -->
                    <template x-if="!isLoading && results.length === 0">
                        <tr>
                            <td colspan="6" class="!py-20 text-center">
                                <p class="text-[15px] text-ink-soft">No SKUs match this search.</p>
                                <p class="mt-1 text-[12px] font-mono tabular text-ink-mute">
                                    Try a shorter query, or
                                    <button @click="clearAll()" class="underline underline-offset-2 hover:text-ink">reset filters</button>.
                                </p>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            </div>

            <!-- Pagination controls -->
            <nav x-show="pageCount > 1" x-cloak class="flex items-center justify-between gap-4 border-t border-line-soft py-5" aria-label="Pagination">
                <button @click="goToPage(page - 1)" :disabled="page <= 1"
                        class="btn-press inline-flex items-center gap-1.5 rounded-full border border-line bg-surface px-3.5 py-1.5 text-[12.5px] text-ink-soft hover:bg-paper-soft hover:text-ink disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                    Previous
                </button>

                <ul class="flex items-center gap-1">
                    <template x-for="p in pageNumbers" :key="p + Math.random()">
                        <li>
                            <button x-show="p !== '…'"
                                    @click="goToPage(p)"
                                    :class="p === page ? 'bg-ink text-paper' : 'text-ink-soft hover:bg-paper-soft hover:text-ink'"
                                    class="btn-press inline-flex h-8 min-w-[32px] items-center justify-center rounded-full px-2 text-[12.5px] font-mono tabular"
                                    x-text="p"></button>
                            <span x-show="p === '…'" class="inline-flex h-8 min-w-[32px] items-center justify-center text-ink-mute font-mono tabular">…</span>
                        </li>
                    </template>
                </ul>

                <button @click="goToPage(page + 1)" :disabled="page >= pageCount"
                        class="btn-press inline-flex items-center gap-1.5 rounded-full border border-line bg-surface px-3.5 py-1.5 text-[12.5px] text-ink-soft hover:bg-paper-soft hover:text-ink disabled:opacity-40 disabled:cursor-not-allowed">
                    Next
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                </button>
            </nav>

            <p class="py-6 text-[11px] font-mono text-ink-mute tabular text-center">
                <span x-show="pageCount > 1" x-cloak>
                    Page <span class="text-ink" x-text="page"></span> of <span class="text-ink" x-text="pageCount"></span> · <span class="text-ink" x-text="pageSize"></span> per page  ·
                </span>
                Last import: <?= $lastImport ? View::e((string) $lastImport['created_at']) : '—' ?>
            </p>
        </div>
    </section>

    <!-- ───────── Detail POPUP — sizes-first, two panels side-by-side on desktop ───────── -->
    <div x-show="active" x-cloak @click.self="cancelEditing(); active = null"
         class="fixed inset-0 z-40 flex items-start sm:items-center justify-center bg-ink/45 backdrop-blur-md p-4 sm:p-6 overflow-y-auto">
        <div class="w-full max-w-[900px] my-6 rounded-3xl border border-line bg-surface shadow-pop slide-in overflow-hidden">
            <template x-if="active">
                <div>
                    <!-- Header: SKU + name + actions + global unit toggle -->
                    <header class="flex items-start justify-between gap-4 border-b border-line-soft px-7 pt-6 pb-5 lg:px-9">
                        <div class="min-w-0">
                            <p class="label !text-[10px]">
                                <span class="text-ember" x-text="active?.category?.code"></span>
                                <span class="text-ink-faint mx-1">·</span>
                                <span x-text="active?.category?.name"></span>
                            </p>
                            <p class="mt-1.5">
                                <span class="inline-block rounded-md bg-ember/[0.08] border border-ember/30 px-2.5 py-1 font-mono font-semibold text-[22px] tabular tracking-tight text-ink shadow-[inset_0_-2px_0_rgba(194,65,12,0.35)]" x-text="active?.sku"></span>
                            </p>
                            <p class="mt-2 text-[14px] text-ink-soft leading-snug" x-text="active?.description"></p>
                            <p x-show="active?.variant" class="mt-0.5 text-[12px] text-ink-mute" x-text="active?.variant"></p>
                        </div>
                        <div class="flex flex-col items-end gap-3 shrink-0">
                            <div class="flex items-center gap-1.5">
                                <button x-show="canEdit && !editMode" @click="startEditing()" class="btn-press inline-flex items-center gap-1.5 rounded-full border border-line bg-surface px-3 py-1.5 text-[12px] text-ink-soft hover:bg-paper-soft" title="Edit">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
                                    Edit
                                </button>
                                <button @click="togglePin(active)" class="btn-press rounded-full border border-line bg-surface p-1.5 hover:bg-paper-soft" :class="active && isPinned(active.sku) ? 'text-ember border-ember/50' : 'text-ink-soft'" :aria-label="active && isPinned(active.sku) ? 'Unpin' : 'Pin'">
                                    <svg width="14" height="14" viewBox="0 0 24 24" :fill="active && isPinned(active.sku) ? 'currentColor' : 'none'" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 17v5"/><path d="M9 2h6l-1 7h3l-5 8-5-8h3z"/></svg>
                                </button>
                                <button @click="cancelEditing(); active = null" class="btn-press rounded-full border border-line bg-surface p-1.5 text-ink-soft hover:bg-paper-soft" aria-label="Close">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12"/><path d="M6 18L18 6"/></svg>
                                </button>
                            </div>
                            <!-- Single unit toggle controls BOTH panels -->
                            <div class="inline-flex rounded-full border border-line bg-paper p-0.5 text-[11px] font-mono tabular">
                                <button @click="unitsMode='metric'" :class="unitsMode==='metric' ? 'bg-ink text-paper' : 'text-ink-soft hover:text-ink'" class="btn-press rounded-full px-2.5 py-1 uppercase tracking-[0.1em]">cm·kg</button>
                                <button @click="unitsMode='imperial'" :class="unitsMode==='imperial' ? 'bg-ink text-paper' : 'text-ink-soft hover:text-ink'" class="btn-press rounded-full px-2.5 py-1 uppercase tracking-[0.1em]">in·lb</button>
                            </div>
                        </div>
                    </header>

                    <!-- ──────── EDIT FORM (admin) ──────── -->
                    <template x-if="editMode">
                        <form @submit.prevent="saveEditing()" class="grid gap-5 border-b border-line-soft bg-ember/[0.03] px-7 py-6 lg:px-9">
                            <p class="text-[11.5px] text-ink-soft leading-snug">Edits save to the database. Next workbook re-import overwrites them — update the Excel too for permanent changes.</p>
                            <template x-for="group in EDIT_GROUPS" :key="group.title">
                                <fieldset class="grid gap-2.5">
                                    <legend class="label !text-[10px]" x-text="group.title"></legend>
                                    <div class="grid grid-cols-2 gap-2.5">
                                        <template x-for="f in group.fields" :key="f.key">
                                            <label class="grid gap-1">
                                                <span class="text-[10.5px] text-ink-mute" x-text="f.label"></span>
                                                <input :type="f.type" :step="f.step || null"
                                                       x-model="editForm[f.key]"
                                                       :class="editErrors[f.key] ? 'border-red-400' : 'border-line'"
                                                       class="w-full rounded-lg border bg-surface px-2.5 py-1.5 text-[12.5px] font-mono tabular focus:outline-none focus:border-ember">
                                                <span x-show="editErrors[f.key]" class="text-[10.5px] text-red-600" x-text="editErrors[f.key]"></span>
                                            </label>
                                        </template>
                                    </div>
                                </fieldset>
                            </template>
                            <details class="rounded-xl border border-line bg-surface px-3 py-2">
                                <summary class="cursor-pointer label !text-[10px]">Imperial dimensions</summary>
                                <div class="mt-3 grid gap-4">
                                    <template x-for="group in EDIT_IMPERIAL_GROUPS" :key="group.title">
                                        <fieldset class="grid gap-2.5">
                                            <legend class="label !text-[10px]" x-text="group.title"></legend>
                                            <div class="grid grid-cols-2 gap-2.5">
                                                <template x-for="f in group.fields" :key="f.key">
                                                    <label class="grid gap-1">
                                                        <span class="text-[10.5px] text-ink-mute" x-text="f.label"></span>
                                                        <input :type="f.type" :step="f.step || null"
                                                               x-model="editForm[f.key]"
                                                               :class="editErrors[f.key] ? 'border-red-400' : 'border-line'"
                                                               class="w-full rounded-lg border bg-paper-soft px-2.5 py-1.5 text-[12.5px] font-mono tabular focus:outline-none focus:border-ember">
                                                    </label>
                                                </template>
                                            </div>
                                        </fieldset>
                                    </template>
                                </div>
                            </details>
                            <div class="flex items-center justify-between">
                                <button type="button" @click="cancelEditing()" class="pill ghost">Cancel</button>
                                <button type="submit" :disabled="editSaving" class="pill"><span x-text="editSaving ? 'Saving…' : 'Save changes'"></span></button>
                            </div>
                        </form>
                    </template>

                    <template x-if="!editMode && editMessage">
                        <div :class="editMessage.type === 'ok' ? 'border-b border-ember/30 bg-ember/[0.05] text-ink' : 'border-b border-red-300 bg-red-50 text-red-900'"
                             class="flex items-center gap-2 px-7 py-2.5 text-[13px] lg:px-9">
                            <span class="font-mono text-[11px]" x-text="editMessage.type === 'ok' ? '✓' : '✗'"></span>
                            <span x-text="editMessage.text"></span>
                        </div>
                    </template>

                    <!-- ════════════════════════════════════════════════════════════════
                         TWO-PANEL SIZE BLOCK — equally important, visually distinct.
                         Stacked on mobile, side-by-side from md (≥768px).
                         ════════════════════════════════════════════════════════════════ -->
                    <div x-show="!editMode" class="grid grid-cols-1 md:grid-cols-2 md:divide-x divide-line border-b border-line">

                        <!-- ────── PER ITEM ────── -->
                        <section class="bg-surface px-6 pt-6 pb-7 lg:px-7">
                            <div class="flex items-center gap-3 mb-5">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-line bg-paper-soft shrink-0" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="text-ink">
                                        <rect x="5" y="5" width="14" height="14" rx="2"/>
                                    </svg>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-[17px] font-semibold tracking-tightest leading-none">Per item</p>
                                    <p class="mt-1 text-[10.5px] text-ink-mute font-mono uppercase tracking-[0.14em]">One piece</p>
                                </div>
                            </div>

                            <!-- L × W × H -->
                            <div class="grid grid-cols-3 gap-2">
                                <template x-for="kv in heroDims(active, 'unit')" :key="kv.label">
                                    <div class="flex flex-col items-start min-w-0">
                                        <p class="headline text-[36px] lg:text-[42px] !leading-[0.95] tabular font-mono truncate w-full" x-text="kv.value"></p>
                                        <p class="mt-2 label !text-[9.5px]" x-text="kv.label"></p>
                                    </div>
                                </template>
                            </div>

                            <!-- Stats row: GROSS WEIGHT highlighted, Volume secondary -->
                            <div class="mt-6 grid grid-cols-2 gap-3 pt-5 border-t border-line-soft">
                                <!-- GROSS WEIGHT — highlighted with ember accent bar -->
                                <div class="relative rounded-2xl border border-ember/30 bg-ember/[0.06] px-4 py-3">
                                    <span class="absolute -top-2 left-3 bg-ember text-paper text-[9px] font-mono uppercase tracking-[0.14em] px-1.5 py-0.5 rounded-full font-semibold">Gross weight</span>
                                    <p class="mt-1 font-mono tabular text-[26px] font-semibold text-ink leading-none" x-text="heroExtras(active, 'unit').weight"></p>
                                </div>
                                <!-- Volume — regular -->
                                <div class="rounded-2xl border border-line bg-paper px-4 py-3">
                                    <p class="label !text-[9.5px]">Volume</p>
                                    <p class="mt-1.5 font-mono tabular text-[20px] font-medium text-ink-soft leading-none" x-text="heroExtras(active, 'unit').volume"></p>
                                </div>
                            </div>
                        </section>

                        <!-- ────── PER BOX ────── -->
                        <section x-show="hasBoxData(active)"
                                 class="relative px-7 pt-7 pb-8 lg:px-8"
                                 style="background: var(--paper-deep); box-shadow: inset 4px 0 0 var(--ember);">
                            <div class="flex items-start justify-between gap-3 mb-5">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-line-strong bg-surface shrink-0" aria-hidden="true">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="text-ember">
                                            <rect x="3"  y="3"  width="8" height="8" rx="1.2"/>
                                            <rect x="13" y="3"  width="8" height="8" rx="1.2"/>
                                            <rect x="3"  y="13" width="8" height="8" rx="1.2"/>
                                            <rect x="13" y="13" width="8" height="8" rx="1.2"/>
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="text-[17px] font-semibold tracking-tightest leading-none">Per box</p>
                                        <p class="mt-1 text-[10.5px] text-ink-mute font-mono uppercase tracking-[0.14em]">
                                            <template x-if="active?.units_per_box">
                                                <span><span class="text-ember font-semibold" x-text="active?.units_per_box"></span> pieces inside</span>
                                            </template>
                                            <template x-if="active && !active.units_per_box">
                                                <span>Outer carton</span>
                                            </template>
                                        </p>
                                    </div>
                                </div>
                                <template x-if="active?.units_per_box">
                                    <span class="inline-flex items-center rounded-full bg-ember text-paper px-2.5 py-1 font-mono text-[12px] font-semibold tabular shrink-0">
                                        × <span x-text="active?.units_per_box"></span>
                                    </span>
                                </template>
                            </div>

                            <!-- L × W × H -->
                            <div class="grid grid-cols-3 gap-2">
                                <template x-for="kv in heroDims(active, 'box')" :key="kv.label">
                                    <div class="flex flex-col items-start min-w-0">
                                        <p class="headline text-[36px] lg:text-[42px] !leading-[0.95] tabular font-mono truncate w-full" x-text="kv.value"></p>
                                        <p class="mt-2 label !text-[9.5px]" x-text="kv.label"></p>
                                    </div>
                                </template>
                            </div>

                            <!-- Stats row: GROSS WEIGHT + UNITS/BOX highlighted, Volume secondary -->
                            <div class="mt-7 grid grid-cols-2 gap-4 pt-6 border-t border-line-strong/60">
                                <!-- GROSS WEIGHT — highlighted -->
                                <div class="relative rounded-2xl border border-ember/40 bg-ember/[0.08] px-5 py-4">
                                    <span class="absolute -top-2 left-3 bg-ember text-paper text-[9px] font-mono uppercase tracking-[0.14em] px-1.5 py-0.5 rounded-full font-semibold">Gross weight</span>
                                    <p class="mt-1.5 font-mono tabular text-[26px] font-semibold text-ink leading-none" x-text="heroExtras(active, 'box').weight"></p>
                                </div>
                                <!-- Volume -->
                                <div class="rounded-2xl border border-line bg-surface/70 px-5 py-4">
                                    <p class="label !text-[9.5px]">Volume</p>
                                    <p class="mt-2 font-mono tabular text-[20px] font-medium text-ink-soft leading-none" x-text="heroExtras(active, 'box').volume"></p>
                                </div>
                            </div>

                            <!-- UNITS PER BOX — full-width highlighted band so it can't be missed -->
                            <div x-show="active?.units_per_box" class="mt-5 relative rounded-2xl border border-ember/40 bg-ember/[0.08] px-5 py-4 flex items-baseline justify-between gap-3">
                                <span class="absolute -top-2 left-3 bg-ember text-paper text-[9px] font-mono uppercase tracking-[0.14em] px-1.5 py-0.5 rounded-full font-semibold">Units per box</span>
                                <p class="mt-1.5 font-mono tabular text-[26px] font-semibold text-ink leading-none">
                                    <span x-text="active?.units_per_box"></span>
                                    <span class="text-[14px] font-medium text-ink-soft ml-1">pieces</span>
                                </p>
                                <p class="font-mono tabular text-[11px] text-ink-mute uppercase tracking-[0.14em]">
                                    × <span x-text="active?.units_per_box"></span> per carton
                                </p>
                            </div>
                        </section>

                        <!-- No box data: friendly empty state IN the right panel slot -->
                        <section x-show="!hasBoxData(active)"
                                 class="relative flex flex-col items-start justify-center px-6 py-7 lg:px-7"
                                 style="background: var(--paper-deep); box-shadow: inset 4px 0 0 var(--line-strong);">
                            <div class="flex items-center gap-3 mb-4">
                                <span class="flex h-10 w-10 items-center justify-center rounded-xl border border-line-strong bg-surface" aria-hidden="true">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="text-ink-dim">
                                        <rect x="3"  y="3"  width="8" height="8" rx="1.2"/>
                                        <rect x="13" y="3"  width="8" height="8" rx="1.2"/>
                                        <rect x="3"  y="13" width="8" height="8" rx="1.2"/>
                                        <rect x="13" y="13" width="8" height="8" rx="1.2"/>
                                    </svg>
                                </span>
                                <div>
                                    <p class="text-[17px] font-semibold tracking-tightest leading-none text-ink-soft">Per box</p>
                                    <p class="mt-1 text-[10.5px] text-ink-mute font-mono uppercase tracking-[0.14em]">No carton on file</p>
                                </div>
                            </div>
                            <p class="text-[12.5px] text-ink-soft leading-snug">
                                This SKU ships loose — no box dimensions in the workbook.
                                <span x-show="canEdit" class="block mt-2 text-[11.5px] text-ink-mute">
                                    Add them via <button @click="startEditing()" class="underline underline-offset-2 hover:text-ink">Edit</button>.
                                </span>
                            </p>
                        </section>
                    </div>

                    <!-- Identifiers — compact, dim. -->
                    <section x-show="!editMode" class="border-t border-line-soft px-7 py-5 bg-paper-soft/60 lg:px-9">
                        <p class="label !text-[10px] mb-3">Identifiers</p>
                        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1.5 text-[12.5px]">
                            <div class="flex justify-between gap-3"><dt class="text-ink-soft">Internal</dt><dd class="font-mono tabular text-ink" x-text="active?.barcodes?.internal || '—'"></dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-ink-soft">GTIN / EAN</dt><dd class="font-mono tabular text-ink" x-text="active?.barcodes?.gtin || '—'"></dd></div>
                            <div class="flex justify-between gap-3"><dt class="text-ink-soft">Box barcode</dt><dd class="font-mono tabular text-ink" x-text="active?.barcodes?.box || '—'"></dd></div>
                        </dl>
                    </section>

                    <!-- Photos — demoted to a small horizontal strip at the bottom. -->
                    <section x-show="!editMode && active && (active.photos?.product || active.photos?.internal_barcode || active.photos?.gtin_barcode || active.photos?.box || active.photos?.box_barcode)"
                             class="border-t border-line-soft px-7 py-5 lg:px-9">
                        <p class="label !text-[10px] mb-3">Photos</p>
                        <div class="flex items-center gap-2 overflow-x-auto">
                            <template x-for="kind in ['product','internal_barcode','gtin_barcode','box','box_barcode']" :key="kind">
                                <button x-show="active?.photos?.[kind]"
                                        @click="openLightbox(lightboxPhotosForActive(), lightboxPhotosForActive().findIndex(it => it.url === active?.photos?.[kind]))"
                                        class="relative h-14 w-14 shrink-0 overflow-hidden rounded-lg border border-line bg-paper transition-shadow hover:shadow-card cursor-zoom-in"
                                        :title="kind.replace('_',' ')">
                                    <img :src="active?.photos?.[kind]" :alt="kind" class="h-full w-full object-contain p-1">
                                </button>
                            </template>
                        </div>
                    </section>

                    <!-- Footer action -->
                    <footer x-show="!editMode" class="flex items-center justify-between gap-3 border-t border-line-soft px-7 py-4 lg:px-9">
                        <p class="text-[11px] font-mono text-ink-mute tabular">Click any photo to enlarge</p>
                        <a :href="active ? DECKO.url + 'sku/' + encodeURIComponent(active.sku) + '/spec' : '#'" target="_blank" rel="noopener" class="pill ghost text-[12px] py-1.5 px-3">
                            Spec sheet
                        </a>
                    </footer>
                </div>
            </template>
        </div>
    </div>

    <!-- ───────── Bookmarks / browse sheet ───────── -->
    <div x-show="bookmarksOpen" x-cloak @click.self="bookmarksOpen = false"
         class="fixed inset-0 z-40 flex items-start justify-center pt-20 bg-ink/35 backdrop-blur-sm px-4">
        <div class="w-full max-w-md rounded-2xl border border-line bg-surface shadow-pop overflow-hidden slide-in">
            <header class="flex items-center justify-between border-b border-line px-4 py-3">
                <p class="label !text-[10px]">Browse</p>
                <button @click="bookmarksOpen=false" class="btn-press rounded-full border border-line bg-surface p-1 px-2 text-[11px] text-ink-soft hover:bg-paper-soft">Esc</button>
            </header>
            <div class="p-4 space-y-5 text-[13px] max-h-[70vh] overflow-auto">
                <section x-show="pins.length" x-cloak>
                    <p class="label !text-[10px] mb-2">Pinned</p>
                    <ul class="divide-y divide-line-soft">
                        <template x-for="p in pins" :key="p.sku">
                            <li class="flex items-center gap-2 py-1.5">
                                <button @click="bookmarksOpen=false; openDetail(p.sku)" class="flex-1 text-left flex items-baseline gap-2 hover:text-ink">
                                    <span class="font-mono text-[11px] font-semibold text-ember" x-text="p.code"></span>
                                    <span class="font-mono text-[13px] tabular" x-text="p.sku"></span>
                                </button>
                                <button @click="togglePin(p)" class="text-ember p-1">★</button>
                            </li>
                        </template>
                    </ul>
                </section>
                <section x-show="recents.length" x-cloak>
                    <div class="flex items-center justify-between mb-2">
                        <p class="label !text-[10px]">Recently viewed</p>
                        <button @click="clearRecents()" class="text-[11px] font-mono text-ink-mute hover:text-ink">Clear</button>
                    </div>
                    <ul class="divide-y divide-line-soft">
                        <template x-for="r in recents" :key="r.sku">
                            <li class="flex items-center gap-2 py-1.5">
                                <button @click="bookmarksOpen=false; openDetail(r.sku)" class="flex-1 text-left flex items-baseline gap-2 hover:text-ink">
                                    <span class="font-mono text-[11px] font-semibold text-ember" x-text="r.code"></span>
                                    <span class="font-mono text-[13px] tabular" x-text="r.sku"></span>
                                </button>
                                <button @click="togglePin(r)" class="p-1" :class="isPinned(r.sku) ? 'text-ember' : 'text-ink-dim hover:text-ink'">★</button>
                            </li>
                        </template>
                    </ul>
                </section>
                <section>
                    <p class="label !text-[10px] mb-2">Categories</p>
                    <ul class="divide-y divide-line-soft">
                        <li>
                            <button @click="setCategory(0); bookmarksOpen=false"
                                    :class="categoryId === 0 ? 'text-ember' : 'text-ink-soft hover:text-ink'"
                                    class="flex w-full items-center justify-between py-1.5 tabular font-mono">
                                <span>All</span>
                                <span class="text-ink-mute"><?= (int) $summary['total'] ?></span>
                            </button>
                        </li>
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <button @click="setCategory(<?= (int) $cat['id'] ?>); bookmarksOpen=false"
                                        :class="categoryId === <?= (int) $cat['id'] ?> ? 'text-ember' : 'text-ink-soft hover:text-ink'"
                                        class="flex w-full items-center justify-between py-1.5 tabular">
                                    <span class="font-mono text-[13px]"><span class="font-semibold text-ember"><?= View::e((string) $cat['code']) ?></span> · <?= View::e((string) $cat['name']) ?></span>
                                    <span class="font-mono text-ink-mute text-[12px]"><?= (int) $cat['sku_count'] ?></span>
                                </button>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </section>
            </div>
        </div>
    </div>

    <!-- ───────── Barcode scanner ───────── -->
    <div x-show="scannerOpen" x-cloak @click.self="closeScanner()"
         class="fixed inset-0 z-[55] flex items-center justify-center bg-ink/55 backdrop-blur-md p-4">
        <div class="w-full max-w-md rounded-2xl border border-line bg-surface shadow-pop overflow-hidden slide-in">
            <header class="flex items-start justify-between gap-4 border-b border-line px-5 py-4">
                <div>
                    <p class="label !text-[10px]">Scan barcode</p>
                    <p class="mt-1 text-[14px]">Point the camera at a barcode</p>
                    <p class="mt-0.5 text-[12px] text-ink-soft" x-text="scannerHint"></p>
                </div>
                <button @click="closeScanner()" class="btn-press rounded-full border border-line bg-surface p-1.5 text-ink-soft hover:bg-paper-soft" aria-label="Close">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12"/><path d="M6 18L18 6"/></svg>
                </button>
            </header>
            <div class="relative bg-ink">
                <div id="decko-scanner-target" class="w-full" style="min-height:260px;"></div>
                <div class="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <div class="h-32 w-64 rounded-xl border-2 border-ember/80"></div>
                </div>
            </div>
            <template x-if="scannerError">
                <div class="border-t border-red-200 bg-red-50 px-5 py-3 text-[12.5px] text-red-900">
                    <p class="font-medium">Scanner couldn't start.</p>
                    <p class="mt-0.5 text-[11.5px]" x-text="scannerError"></p>
                    <p class="mt-1 text-[11px] text-red-700/80">No camera? Type the barcode into the search bar — same result.</p>
                </div>
            </template>
        </div>
    </div>

    <!-- ───────── Photo lightbox ───────── -->
    <div x-show="lightboxOpen" x-cloak @click.self="closeLightbox()"
         class="fixed inset-0 z-[60] flex flex-col bg-ink/95 backdrop-blur-md no-print">
        <header class="flex items-center justify-between gap-4 px-5 py-3 text-white">
            <div class="min-w-0">
                <p class="font-mono text-[10px] uppercase tracking-[0.16em] text-white/60">Photo</p>
                <p class="mt-0.5 text-[14px] tabular truncate" x-text="lightboxItems[lightboxIndex]?.label || ''"></p>
            </div>
            <div class="flex items-center gap-1">
                <button @click="lightboxSetZoom(lightboxZoom - 0.5)" class="btn-press rounded-full border border-white/15 bg-white/5 p-2 text-white/80 hover:bg-white/10" aria-label="Zoom out">−</button>
                <button @click="lightboxResetZoom()" class="btn-press rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-[11px] font-mono tabular text-white/80 hover:bg-white/10"><span x-text="lightboxZoom.toFixed(1) + 'x'"></span></button>
                <button @click="lightboxSetZoom(lightboxZoom + 0.5)" class="btn-press rounded-full border border-white/15 bg-white/5 p-2 text-white/80 hover:bg-white/10" aria-label="Zoom in">+</button>
                <a :href="lightboxItems[lightboxIndex]?.url"
                   :download="(active?.sku || 'photo') + '__' + (lightboxItems[lightboxIndex]?.label || '').replace(/\s+/g,'_') + '.png'"
                   class="btn-press rounded-full border border-white/15 bg-white/5 p-2 text-white/80 hover:bg-white/10">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12"/><path d="M7 10l5 5 5-5"/><path d="M5 21h14"/></svg>
                </a>
                <button @click="closeLightbox()" class="btn-press rounded-full border border-white/15 bg-white/5 p-2 text-white/80 hover:bg-white/10">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12"/><path d="M6 18L18 6"/></svg>
                </button>
            </div>
        </header>
        <div @wheel.prevent="lightboxOnWheel($event)"
             @pointerdown="lightboxOnPointerDown($event)"
             @pointermove="lightboxOnPointerMove($event)"
             @pointerup="lightboxOnPointerUp()"
             @pointercancel="lightboxOnPointerUp()"
             @click.self="closeLightbox()"
             class="relative flex flex-1 items-center justify-center overflow-hidden select-none touch-none"
             :class="lightboxZoom > 1 ? 'cursor-grab active:cursor-grabbing' : 'cursor-zoom-in'">
            <template x-if="lightboxItems[lightboxIndex]">
                <img :src="lightboxItems[lightboxIndex].url"
                     :alt="lightboxItems[lightboxIndex].label"
                     @dblclick.stop="lightboxToggleZoom()"
                     @click.stop
                     draggable="false"
                     class="max-h-full max-w-full select-none object-contain will-change-transform transition-transform duration-150 ease-out"
                     :style="`transform: translate(${lightboxOffsetX}px, ${lightboxOffsetY}px) scale(${lightboxZoom}); transform-origin: center;`">
            </template>
            <template x-if="lightboxItems.length > 1">
                <div class="pointer-events-none absolute inset-x-0 top-1/2 flex -translate-y-1/2 justify-between px-3 md:px-6">
                    <button @click.stop="lightboxPrev()" class="btn-press pointer-events-auto rounded-full border border-white/15 bg-white/10 px-3 py-2 text-white/80 hover:text-white">‹</button>
                    <button @click.stop="lightboxNext()" class="btn-press pointer-events-auto rounded-full border border-white/15 bg-white/10 px-3 py-2 text-white/80 hover:text-white">›</button>
                </div>
            </template>
        </div>
        <footer x-show="lightboxItems.length > 1" class="flex items-center justify-center gap-2 border-t border-white/10 px-3 py-2">
            <template x-for="(item, idx) in lightboxItems" :key="idx">
                <button @click="lightboxIndex = idx; lightboxResetZoom()"
                        :class="idx === lightboxIndex ? 'border-white/80' : 'border-white/15 opacity-50 hover:opacity-100'"
                        class="btn-press relative h-12 w-12 overflow-hidden rounded-lg border bg-white/5">
                    <img :src="item.url" :alt="item.label" class="h-full w-full object-contain p-1">
                </button>
            </template>
        </footer>
    </div>
</div>

<script>
    window.DECKO_BOOT = {
        categories: <?= json_encode(array_map(static fn ($c) => [
            'id'   => (int) $c['id'],
            'code' => $c['code'],
            'name' => $c['name'],
        ], $categories)) ?>,
    };
</script>
