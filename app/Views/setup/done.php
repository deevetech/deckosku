<?php
/** @var array<int,string> $log */
/** @var string $email */
use App\Core\View;
?>
<main class="min-h-[100dvh] bg-paper flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-2xl rounded-3xl border border-line bg-surface shadow-card p-8 sm:p-10">
        <header class="flex items-center gap-3 mb-6">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-ink text-paper font-mono text-[14px] font-semibold">D</span>
            <div>
                <p class="font-mono text-[10.5px] uppercase tracking-[0.18em] text-ink-mute">DECKO · Installer</p>
                <h1 class="mt-1 text-[22px] font-semibold tracking-tight">Setup complete.</h1>
            </div>
        </header>

        <p class="text-[13.5px] text-ink-soft leading-snug">
            The schema is in place, an admin account has been created, and the workbook has been imported (if provided).
            Sign in as <span class="font-mono"><?= View::e($email) ?></span> to start using the catalogue.
        </p>

        <section class="mt-6 rounded-2xl border border-line bg-paper-soft/60 px-5 py-4">
            <p class="label !text-[10px] mb-2">Install log</p>
            <ul class="space-y-1.5 text-[12.5px] font-mono tabular text-ink-soft">
                <?php foreach ($log as $line): ?>
                    <li>· <?= View::e($line) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <div class="mt-7 flex items-center justify-between">
            <p class="text-[11.5px] text-ink-mute">The setup route is now locked.</p>
            <a href="<?= View::url('login') ?>" class="inline-flex items-center rounded-full bg-ink text-paper px-5 py-2.5 text-[13.5px] font-medium">Go to login</a>
        </div>
    </div>
</main>
