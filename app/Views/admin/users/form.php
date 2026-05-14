<?php
/** @var string $mode */
/** @var array<string,mixed>|null $user */
/** @var string $errors */
/** @var string $old */
/** @var string $csrfToken */
use App\Core\View;

$errs = $errors ? (array) json_decode($errors, true) : [];
$prev = $old    ? (array) json_decode($old,    true) : [];
$value = static function (string $field) use ($user, $prev): string {
    if (isset($prev[$field])) return (string) $prev[$field];
    if (is_array($user) && isset($user[$field])) return (string) $user[$field];
    return '';
};
$err = static fn (string $field) => $errs[$field] ?? null;
$isEdit = $mode === 'edit';
$action = $isEdit && is_array($user) ? View::url('admin/users/' . (int) $user['id']) : View::url('admin/users');
$role   = $value('role') ?: 'viewer';
?>
<div class="min-h-[100dvh]">
    <header class="border-b border-line bg-paper/80 backdrop-blur-md sticky top-0 z-20">
        <div class="mx-auto flex max-w-2xl items-center justify-between gap-4 px-6 py-4">
            <a href="<?= View::url('admin/users') ?>" class="inline-flex items-center gap-2 text-[13px] text-ink-soft hover:text-ink">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                Back to team
            </a>
            <?php if ($isEdit && is_array($user)): ?>
                <span class="font-mono text-[11px] text-ink-mute tabular">User · <?= (int) $user['id'] ?></span>
            <?php endif; ?>
        </div>
    </header>

    <main class="mx-auto max-w-2xl px-6 py-10">
        <p class="label">Admin · Team</p>
        <h1 class="headline mt-3 text-[36px]"><?= $isEdit ? 'Edit user' : 'Add a new user' ?></h1>

        <form method="post" action="<?= View::e($action) ?>" class="mt-8 grid gap-5 rounded-2xl border border-line bg-surface p-6 shadow-card md:p-8">
            <input type="hidden" name="_token" value="<?= View::e($csrfToken) ?>">

            <label class="grid gap-1.5">
                <span class="text-[12.5px] font-medium">Full name</span>
                <input type="text" name="name" required value="<?= View::e($value('name')) ?>"
                       class="w-full rounded-xl border <?= $err('name') ? 'border-red-300' : 'border-line' ?> bg-surface px-4 py-3 text-[14px] focus:border-ink focus:outline-none focus:ring-0">
                <?php if ($err('name')): ?><span class="text-[11.5px] text-red-700"><?= View::e($err('name')) ?></span><?php endif; ?>
            </label>

            <label class="grid gap-1.5">
                <span class="text-[12.5px] font-medium">Email</span>
                <input type="email" name="email" required value="<?= View::e($value('email')) ?>"
                       class="w-full rounded-xl border <?= $err('email') ? 'border-red-300' : 'border-line' ?> bg-surface px-4 py-3 text-[14px] font-mono tabular focus:border-ink focus:outline-none focus:ring-0">
                <?php if ($err('email')): ?><span class="text-[11.5px] text-red-700"><?= View::e($err('email')) ?></span><?php endif; ?>
            </label>

            <fieldset class="grid gap-2">
                <legend class="text-[12.5px] font-medium">Role</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border <?= $role === 'admin' ? 'border-ink bg-paper-soft' : 'border-line bg-paper hover:bg-paper-soft' ?> p-4 transition-colors">
                        <input type="radio" name="role" value="admin" <?= $role === 'admin' ? 'checked' : '' ?> class="mt-0.5 accent-ink">
                        <span>
                            <span class="block text-[13.5px] font-medium">Admin</span>
                            <span class="mt-0.5 block text-[12px] text-ink-soft">Edit SKUs · re-import · manage users.</span>
                        </span>
                    </label>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border <?= $role === 'viewer' ? 'border-ink bg-paper-soft' : 'border-line bg-paper hover:bg-paper-soft' ?> p-4 transition-colors">
                        <input type="radio" name="role" value="viewer" <?= $role === 'viewer' ? 'checked' : '' ?> class="mt-0.5 accent-ink">
                        <span>
                            <span class="block text-[13.5px] font-medium">Viewer</span>
                            <span class="mt-0.5 block text-[12px] text-ink-soft">Search · view · export. Read-only.</span>
                        </span>
                    </label>
                </div>
                <?php if ($err('role')): ?><span class="text-[11.5px] text-red-700"><?= View::e($err('role')) ?></span><?php endif; ?>
            </fieldset>

            <label class="grid gap-1.5">
                <span class="flex items-center justify-between text-[12.5px] font-medium">
                    <span><?= $isEdit ? 'Reset password (optional)' : 'Password' ?></span>
                    <span class="font-mono text-[10.5px] uppercase tracking-[0.12em] text-ink-mute">8+ characters</span>
                </span>
                <input type="password" name="password" <?= $isEdit ? '' : 'required' ?> autocomplete="new-password"
                       placeholder="<?= $isEdit ? 'Leave blank to keep current' : 'At least 8 characters' ?>"
                       class="w-full rounded-xl border <?= $err('password') ? 'border-red-300' : 'border-line' ?> bg-surface px-4 py-3 text-[14px] focus:border-ink focus:outline-none focus:ring-0">
                <?php if ($err('password')): ?><span class="text-[11.5px] text-red-700"><?= View::e($err('password')) ?></span><?php endif; ?>
            </label>

            <div class="mt-2 flex items-center justify-between gap-3">
                <a href="<?= View::url('admin/users') ?>" class="pill ghost">Cancel</a>
                <button type="submit" class="pill"><?= $isEdit ? 'Save changes' : 'Create user' ?></button>
            </div>
        </form>
    </main>
</div>
