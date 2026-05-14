<?php
/** @var array<int,array<string,mixed>> $users */
/** @var ?string $message */
/** @var ?string $errorMessage */
/** @var string $csrfToken */
use App\Core\Auth;
use App\Core\View;
$meId = Auth::id();
?>
<div class="min-h-[100dvh]">
    <header class="border-b border-line bg-paper/80 backdrop-blur-md sticky top-0 z-20">
        <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-4 lg:px-10">
            <a href="<?= View::url('') ?>" class="inline-flex items-center gap-2 text-[13px] text-ink-soft hover:text-ink">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 6l-6 6 6 6"/></svg>
                Back to dashboard
            </a>
            <a href="<?= View::url('admin/users/new') ?>" class="pill">+ Add user</a>
        </div>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-10 lg:px-10">
        <p class="label">Admin · Team access</p>
        <h1 class="headline mt-3 text-[40px] md:text-[52px]">Who can sign in to the catalogue.</h1>
        <p class="mt-4 max-w-xl text-[15px] text-ink-soft leading-relaxed">
            Admins can edit SKUs, re-import the workbook, and manage users. Viewers can search, view, and export.
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

        <section class="mt-10 overflow-hidden rounded-2xl border border-line bg-surface shadow-card">
            <table class="lux">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th class="hidden md:table-cell">Last sign-in</th>
                        <th class="hidden md:table-cell">Joined</th>
                        <th class="num">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-paper-soft border border-line text-[11px] font-medium shrink-0">
                                        <?= View::e(strtoupper(substr((string) $u['name'], 0, 1))) ?>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="truncate text-[13.5px] font-medium">
                                            <?= View::e((string) $u['name']) ?>
                                            <?php if ((int) $u['id'] === $meId): ?>
                                                <span class="ml-1.5 rounded-full bg-ember/10 px-1.5 py-0.5 font-mono text-[10px] uppercase tracking-[0.12em] text-ember">You</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="truncate font-mono text-[11.5px] text-ink-mute tabular"><?= View::e((string) $u['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php $role = (string) ($u['role'] ?? 'viewer'); ?>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 font-mono text-[10.5px] uppercase tracking-[0.14em] <?= $role === 'admin' ? 'bg-ink text-paper' : 'bg-paper-soft text-ink-soft' ?>">
                                    <?= View::e($role) ?>
                                </span>
                            </td>
                            <td class="hidden md:table-cell font-mono tabular text-[12px] text-ink-mute">
                                <?= $u['last_login_at'] ? View::e((string) $u['last_login_at']) : '—' ?>
                            </td>
                            <td class="hidden md:table-cell font-mono tabular text-[12px] text-ink-mute">
                                <?= View::e((string) $u['created_at']) ?>
                            </td>
                            <td class="num">
                                <div class="inline-flex items-center gap-2">
                                    <a href="<?= View::url('admin/users/' . (int) $u['id']) ?>" class="btn-press rounded-full border border-line bg-surface px-3 py-1 text-[12px] text-ink-soft hover:text-ink hover:bg-paper-soft">Edit</a>
                                    <?php if ((int) $u['id'] !== $meId): ?>
                                        <form method="post" action="<?= View::url('admin/users/' . (int) $u['id'] . '/delete') ?>" class="inline"
                                              onsubmit="return confirm('Delete <?= View::e((string) $u['email']) ?>?');">
                                            <input type="hidden" name="_token" value="<?= View::e($csrfToken) ?>">
                                            <button class="btn-press rounded-full border border-red-200 bg-red-50 px-3 py-1 text-[12px] text-red-700 hover:bg-red-100">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <p class="mt-8 text-[11.5px] font-mono text-ink-mute tabular">
            <?= count($users) ?> total user<?= count($users) === 1 ? '' : 's' ?> ·
            <a href="<?= View::url('me/password') ?>" class="underline underline-offset-2 hover:text-ink">change my own password →</a>
        </p>
    </main>
</div>
