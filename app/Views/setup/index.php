<?php
/** @var array<string,mixed> $env */
/** @var bool $alreadyDone */
/** @var string|null $error */
/** @var array<string,string> $fieldErrors */
/** @var array<string,string> $old */
/** @var string $csrfToken */
use App\Core\View;

$fieldErrors = $fieldErrors ?? [];
$old         = $old ?? [];
$dbReachable = (bool) ($env['db_reachable'] ?? false);
?>
<main class="min-h-[100dvh] bg-paper flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-2xl rounded-3xl border border-line bg-surface shadow-card p-8 sm:p-10">

        <header class="flex items-center gap-3 mb-6">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-ink text-paper font-mono text-[14px] font-semibold">D</span>
            <div>
                <p class="font-mono text-[10.5px] uppercase tracking-[0.18em] text-ink-mute">DECKO · First-run installer</p>
                <h1 class="mt-1 text-[22px] font-semibold tracking-tight">Set up this install</h1>
            </div>
        </header>

        <?php if ($alreadyDone): ?>
            <div class="rounded-2xl border border-line-soft bg-paper-soft px-5 py-4 text-[13.5px]">
                <p class="font-medium">This install is already configured.</p>
                <p class="mt-1 text-ink-soft">Use the regular login to continue, or the admin import page to refresh data.</p>
                <a href="<?= View::url('login') ?>" class="mt-4 inline-flex items-center rounded-full bg-ink text-paper px-4 py-2 text-[12.5px]">Go to login</a>
            </div>
        <?php else: ?>

            <!-- Environment summary -->
            <section class="mb-6 rounded-2xl border border-line bg-paper-soft/60 px-5 py-4 text-[12.5px] font-mono tabular">
                <p class="label !text-[10px] mb-2">Environment</p>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-1.5 text-ink">
                    <div class="flex justify-between gap-3"><dt class="text-ink-soft">APP_ENV</dt><dd><?= View::e((string) ($env['app_env'] ?? '')) ?></dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-ink-soft">DB driver</dt><dd><?= View::e((string) ($env['driver'] ?? '')) ?></dd></div>
                    <?php if (($env['driver'] ?? '') === 'mysql'): ?>
                        <div class="flex justify-between gap-3"><dt class="text-ink-soft">DB host</dt><dd><?= View::e((string) ($env['db_host'] ?? '')) ?></dd></div>
                        <div class="flex justify-between gap-3"><dt class="text-ink-soft">DB name</dt><dd><?= View::e((string) ($env['db_name'] ?? '')) ?></dd></div>
                    <?php else: ?>
                        <div class="flex justify-between gap-3 sm:col-span-2"><dt class="text-ink-soft">DB path</dt><dd class="truncate"><?= View::e((string) ($env['db_path'] ?? '')) ?></dd></div>
                    <?php endif; ?>
                    <div class="flex justify-between gap-3 sm:col-span-2">
                        <dt class="text-ink-soft">Connection</dt>
                        <dd class="<?= $dbReachable ? 'text-ink' : 'text-ember' ?>"><?= $dbReachable ? 'reachable' : 'not reachable' ?></dd>
                    </div>
                </dl>
            </section>

            <?php if ($error !== null): ?>
                <div class="mb-6 rounded-2xl border border-ember/40 bg-ember/[0.06] px-5 py-4 text-[13px] text-ink">
                    <p class="font-medium text-ember">Install halted</p>
                    <pre class="mt-2 whitespace-pre-wrap break-words font-mono text-[12px] text-ink-soft"><?= View::e($error) ?></pre>
                </div>
            <?php endif; ?>

            <form method="post" action="<?= View::url('setup') ?>" enctype="multipart/form-data" class="grid gap-5"
                  <?= $dbReachable ? '' : 'aria-disabled="true"' ?>>
                <input type="hidden" name="_token" value="<?= View::e($csrfToken) ?>">

                <fieldset class="grid gap-4" <?= $dbReachable ? '' : 'disabled' ?>>
                    <legend class="label !text-[10px] mb-1">Admin account</legend>

                    <label class="grid gap-1.5">
                        <span class="text-[12.5px] font-medium">Full name</span>
                        <input type="text" name="admin_name" maxlength="100" required autocomplete="name"
                               value="<?= View::e($old['admin_name'] ?? '') ?>"
                               class="rounded-xl border <?= isset($fieldErrors['admin_name']) ? 'border-ember' : 'border-line' ?> bg-surface px-4 py-3 text-[14px] focus:border-ink focus:outline-none focus:ring-0">
                        <?php if (isset($fieldErrors['admin_name'])): ?>
                            <span class="text-[11.5px] text-ember"><?= View::e($fieldErrors['admin_name']) ?></span>
                        <?php endif; ?>
                    </label>

                    <label class="grid gap-1.5">
                        <span class="text-[12.5px] font-medium">Email</span>
                        <input type="email" name="admin_email" maxlength="190" required autocomplete="email"
                               value="<?= View::e($old['admin_email'] ?? '') ?>"
                               class="rounded-xl border <?= isset($fieldErrors['admin_email']) ? 'border-ember' : 'border-line' ?> bg-surface px-4 py-3 text-[14px] font-mono tabular focus:border-ink focus:outline-none focus:ring-0">
                        <?php if (isset($fieldErrors['admin_email'])): ?>
                            <span class="text-[11.5px] text-ember"><?= View::e($fieldErrors['admin_email']) ?></span>
                        <?php endif; ?>
                    </label>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="grid gap-1.5">
                            <span class="text-[12.5px] font-medium">Password</span>
                            <input type="password" name="admin_password" minlength="12" required autocomplete="new-password"
                                   class="rounded-xl border <?= isset($fieldErrors['admin_password']) ? 'border-ember' : 'border-line' ?> bg-surface px-4 py-3 text-[14px] font-mono focus:border-ink focus:outline-none focus:ring-0">
                            <span class="text-[11px] text-ink-mute">Minimum 12 characters.</span>
                            <?php if (isset($fieldErrors['admin_password'])): ?>
                                <span class="text-[11.5px] text-ember"><?= View::e($fieldErrors['admin_password']) ?></span>
                            <?php endif; ?>
                        </label>
                        <label class="grid gap-1.5">
                            <span class="text-[12.5px] font-medium">Confirm password</span>
                            <input type="password" name="admin_password_confirm" minlength="12" required autocomplete="new-password"
                                   class="rounded-xl border <?= isset($fieldErrors['admin_password_confirm']) ? 'border-ember' : 'border-line' ?> bg-surface px-4 py-3 text-[14px] font-mono focus:border-ink focus:outline-none focus:ring-0">
                            <?php if (isset($fieldErrors['admin_password_confirm'])): ?>
                                <span class="text-[11.5px] text-ember"><?= View::e($fieldErrors['admin_password_confirm']) ?></span>
                            <?php endif; ?>
                        </label>
                    </div>
                </fieldset>

                <fieldset class="grid gap-2.5" <?= $dbReachable ? '' : 'disabled' ?>>
                    <legend class="label !text-[10px] mb-1">Workbook (optional)</legend>
                    <p class="text-[12px] text-ink-soft leading-snug">
                        Upload the DECKO Warehouse <code class="font-mono">.xlsx</code> to seed all SKUs and photos.
                        Skip this if the bundled workbook already lives at <code class="font-mono"><?= View::e((string) \App\Core\Env::get('EXCEL_PATH', 'DECKO Warehouse SKU and Barcode list.xlsx')) ?></code> on the server.
                    </p>
                    <input type="file" name="workbook" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                           class="block w-full rounded-xl border <?= isset($fieldErrors['workbook']) ? 'border-ember' : 'border-line border-dashed' ?> bg-paper px-3 py-3 text-[13px] file:mr-3 file:rounded-md file:border-0 file:bg-ink file:px-3 file:py-1.5 file:text-paper file:text-[12px]">
                    <?php if (isset($fieldErrors['workbook'])): ?>
                        <span class="text-[11.5px] text-ember"><?= View::e($fieldErrors['workbook']) ?></span>
                    <?php endif; ?>
                </fieldset>

                <div class="mt-2 flex items-center justify-between gap-3">
                    <p class="text-[11.5px] text-ink-mute">Setup runs once. After this, the wizard locks itself.</p>
                    <button type="submit"
                            class="inline-flex items-center rounded-full bg-ink text-paper px-5 py-2.5 text-[13.5px] font-medium disabled:opacity-50"
                            <?= $dbReachable ? '' : 'disabled' ?>>
                        Run setup
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</main>
