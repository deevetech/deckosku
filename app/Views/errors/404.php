<?php use App\Core\View; ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>404 — not found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="<?= View::asset('css/app.css?v=4') ?>">
</head>
<body class="min-h-[100dvh] bg-paper text-ink antialiased">
    <main class="mx-auto flex min-h-[100dvh] max-w-md flex-col items-start justify-center px-6">
        <p class="label">Error · 404</p>
        <h1 class="headline mt-3 text-[40px]">Nothing at this path.</h1>
        <p class="mt-3 text-[14px] text-ink-soft">The page you tried to open doesn't exist in the size-management dashboard.</p>
        <a href="<?= View::url('') ?>" class="pill mt-7">
            Back to dashboard
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M13 6l6 6-6 6"/></svg>
        </a>
    </main>
</body>
</html>
