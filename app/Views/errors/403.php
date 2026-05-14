<?php use App\Core\View; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>403 — not allowed</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= View::asset('css/app.css?v=4') ?>">
</head>
<body class="min-h-[100dvh] bg-paper text-ink antialiased">
    <main class="mx-auto flex min-h-[100dvh] max-w-md flex-col items-start justify-center px-6">
        <p class="label" style="color: var(--ember);">Error · 403 · denied</p>
        <h1 class="headline mt-3 text-[40px]">Admin access only.</h1>
        <p class="mt-3 text-[14px] text-ink-soft">This route is restricted to administrators. Ask your admin to promote your account if you need access.</p>
        <a href="<?= View::url('') ?>" class="pill mt-7">
            Back to dashboard
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
        </a>
    </main>
</body>
</html>
