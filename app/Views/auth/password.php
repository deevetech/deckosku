<?php
/** @var ?string $message */
/** @var ?string $error */
/** @var string $csrfToken */
/** @var array<string,mixed>|null $user */
use App\Core\View;
?>
<div class="min-h-[100dvh]">
    <header class="border-b border-line bg-paper/80 backdrop-blur-md sticky top-0 z-20">
        <div class="mx-auto flex max-w-xl items-center justify-between gap-4 px-6 py-4">
            <a href="<?= View::url('') ?>" class="inline-flex items-center gap-2 text-[13px] text-ink-soft hover:text-ink">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                Back to dashboard
            </a>
            <span class="font-mono text-[11px] text-ink-mute tabular"><?= View::e((string) ($user['email'] ?? '')) ?></span>
        </div>
    </header>

    <main class="mx-auto max-w-xl px-6 py-10">
        <p class="label">Account</p>
        <h1 class="headline mt-3 text-[36px]">Change password</h1>

        <?php if ($message): ?>
            <div role="status" class="mt-6 flex items-start gap-3 rounded-2xl border border-ember/30 bg-ember/[0.05] p-4 text-[13.5px]">
                <span class="font-mono text-ember">✓</span><span><?= View::e($message) ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div role="alert" class="mt-6 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-[13.5px] text-red-900">
                <span class="font-mono">✗</span><span><?= View::e($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= View::url('me/password') ?>" class="mt-8 grid gap-5 rounded-2xl border border-line bg-surface p-6 shadow-card md:p-8">
            <input type="hidden" name="_token" value="<?= View::e($csrfToken) ?>">

            <label class="grid gap-1.5">
                <span class="text-[12.5px] font-medium">Current password</span>
                <input type="password" name="current" required autocomplete="current-password"
                       class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-[14px] font-mono tabular focus:border-ink focus:outline-none focus:ring-0">
            </label>

            <label class="grid gap-1.5">
                <span class="flex items-center justify-between text-[12.5px] font-medium">
                    <span>New password</span>
                    <span class="font-mono text-[10.5px] uppercase tracking-[0.12em] text-ink-mute">8+ characters</span>
                </span>
                <input type="password" name="password" required minlength="8" autocomplete="new-password"
                       class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-[14px] font-mono tabular focus:border-ink focus:outline-none focus:ring-0">
            </label>

            <label class="grid gap-1.5">
                <span class="text-[12.5px] font-medium">Confirm new password</span>
                <input type="password" name="confirm" required minlength="8" autocomplete="new-password"
                       class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-[14px] font-mono tabular focus:border-ink focus:outline-none focus:ring-0">
            </label>

            <div class="mt-2 flex items-center justify-between gap-3">
                <a href="<?= View::url('') ?>" class="pill ghost">Cancel</a>
                <button type="submit" class="pill">Update password</button>
            </div>
        </form>
    </main>
</div>
