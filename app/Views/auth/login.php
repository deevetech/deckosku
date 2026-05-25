<?php
/** @var ?string $error */
/** @var string $email */
/** @var string $csrfToken */
use App\Core\View;
?>
<main class="grid min-h-[100dvh] grid-cols-1 lg:grid-cols-[1.05fr_1fr]">

    <!-- Editorial side: warehouse aesthetic, no centered hero -->
    <aside class="relative hidden lg:flex lg:flex-col lg:justify-between bg-ink text-paper p-12 xl:p-16 overflow-hidden">
        <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-paper text-ink font-mono text-[14px] font-semibold">D</span>
            <span class="font-mono text-[10.5px] uppercase tracking-[0.18em] text-paper/60">DECKO · Warehouse Ops</span>
        </div>

        <div class="max-w-md">
            <p class="font-mono text-[10.5px] uppercase tracking-[0.18em] text-paper/55">Internal tooling · v1.0</p>
            <h1 class="headline mt-5 text-[44px] xl:text-[58px] !leading-[1] !tracking-tightest">
                Search your warehouse<br>
                <span class="text-ember-soft">at the speed of typing.</span>
            </h1>
            <p class="mt-6 max-w-sm text-[14.5px] text-paper/70 leading-relaxed">
                102 SKUs, 340 product photos, every dimension and barcode — searchable
                in real time. Pulls straight from the master workbook.
            </p>
        </div>

        <div class="flex items-end justify-between text-[11px] font-mono text-paper/45">
            <div>
                <p class="uppercase tracking-[0.18em]">Catalogue</p>
                <p class="mt-1 text-paper/75 text-[13px]">102 · SKUs &nbsp; 7 · categories &nbsp; 340 · photos</p>
            </div>
            <p class="uppercase tracking-[0.18em]"><?= date('Y') ?> · Brisbane Australia</p>
        </div>

        <!-- Decorative grain layer keeps the dark column from feeling sterile. -->
        <div class="pointer-events-none absolute inset-0 opacity-[0.06] mix-blend-overlay"
             style="background-image:url(&quot;data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='200' height='200'><filter id='n'><feTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='2' stitchTiles='stitch'/></filter><rect width='100%' height='100%' filter='url(%23n)'/></svg>&quot;);"></div>
    </aside>

    <!-- Form column -->
    <section class="flex flex-col items-stretch justify-center px-6 py-12 sm:px-12 lg:px-16">
        <div class="mx-auto w-full max-w-sm">
            <!-- Mobile brand mark -->
            <div class="mb-10 flex items-center gap-3 lg:hidden">
                <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-ink text-paper font-mono text-[14px] font-semibold">D</span>
                <span class="font-mono text-[10.5px] uppercase tracking-[0.18em] text-ink-mute">DECKO · Warehouse</span>
            </div>

            <p class="label !text-[10px]">Sign in</p>
            <h2 class="headline mt-2 text-[32px]">Welcome back.</h2>
            <p class="mt-2 text-[13.5px] text-ink-soft">Restricted access — DECKO ops team only.</p>

            <?php if ($error): ?>
                <div role="alert" class="mt-7 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-3.5 text-[13px] text-red-900">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="mt-0.5 shrink-0"><circle cx="12" cy="12" r="9"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>
                    <span><?= View::e($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= View::url('login') ?>" class="mt-8 grid gap-4">
                <input type="hidden" name="_token" value="<?= View::e($csrfToken) ?>">

                <label class="grid gap-1.5">
                    <span class="text-[12.5px] font-medium text-ink">Email</span>
                    <input
                        type="email"
                        name="email"
                        autocomplete="username"
                        required
                        value="<?= View::e($email) ?>"
                        class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-[14px] tabular font-mono placeholder:text-ink-dim focus:border-ink focus:outline-none focus:ring-0"
                        placeholder="you@decko.local">
                </label>

                <label class="grid gap-1.5">
                    <span class="flex items-center justify-between text-[12.5px] font-medium text-ink">
                        <span>Password</span>
                        <span class="font-mono text-[10.5px] uppercase tracking-[0.12em] text-ink-mute">5 tries · 5-min lock</span>
                    </span>
                    <input
                        type="password"
                        name="password"
                        autocomplete="current-password"
                        required
                        class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-[14px] font-mono tabular placeholder:text-ink-dim focus:border-ink focus:outline-none focus:ring-0"
                        placeholder="••••••••••••">
                </label>

                <button
                    type="submit"
                    class="pill mt-3 justify-center py-3 text-[14px]">
                    Sign in
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
                </button>
            </form>

            <p class="mt-10 text-[11.5px] text-ink-mute font-mono uppercase tracking-[0.14em]">
                Lost access? Ask warehouse admin to reset.
            </p>
        </div>
    </section>
</main>
