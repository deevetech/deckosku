<?php
/** @var string $content */
/** @var string $appName */
/** @var array<string,mixed>|null $user */
use App\Core\View;

$assetVersion = '15';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#faf8f5">
    <title><?= View::e($appName) ?></title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='6' fill='%2318171c'/%3E%3Ctext x='16' y='22' text-anchor='middle' font-family='Geist, system-ui, sans-serif' font-weight='600' font-size='15' fill='%23faf8f5'%3ED%3C/text%3E%3C/svg%3E">
    <script src="https://cdn.tailwindcss.com?plugins=forms,typography"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Geist', 'system-ui', '-apple-system', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        paper:        '#faf8f5',
                        'paper-soft': '#f5f2eb',
                        'paper-deep': '#ebe7dd',
                        surface:      '#ffffff',
                        'surface-warm':'#fefcf8',
                        ink:        { DEFAULT: '#18171c', soft: '#43424a', mute: '#6b6873', dim: '#9a96a0', faint: '#c6c1c5' },
                        line:       { DEFAULT: '#e8e2d6', soft: '#f0ebde', strong: '#d8d2c4' },
                        ember:      { DEFAULT: '#c2410c', soft: '#f4d8c3' },
                    },
                    boxShadow: {
                        card: '0 1px 0 rgba(24,23,28,0.04), 0 6px 16px -8px rgba(60,40,20,0.10)',
                        pop:  '0 1px 0 rgba(24,23,28,0.04), 0 20px 40px -16px rgba(60,40,20,0.18)',
                    },
                    borderRadius: { '4xl': '28px', '5xl': '36px' },
                    letterSpacing: { tightest: '-0.022em' },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="<?= View::asset('css/app.css?v=' . $assetVersion) ?>">
    <!-- IMPORTANT load order:
         1. dashboard.js  — defines window.dashboard()
         2. alpine.js     — scans the DOM, evaluates x-data="dashboard()"
         3. html5-qrcode  — used by the scanner only
         All three are deferred and execute in source order before DOMContentLoaded. -->
    <script defer src="<?= View::asset('js/dashboard.js?v=' . $assetVersion) ?>"></script>
    <script defer src="<?= View::asset('js/alpine.js?v=' . $assetVersion) ?>"></script>
    <script defer src="<?= View::asset('js/html5-qrcode.js?v=' . $assetVersion) ?>"></script>
    <script>
        window.DECKO = {
            url:       <?= json_encode(View::url('')) ?>,
            csrfToken: <?= json_encode($csrfToken ?? '') ?>,
            isAdmin:   <?= json_encode((bool) ($isAdmin ?? false)) ?>,
            user:      <?= json_encode([
                'name'  => $user['name']  ?? null,
                'email' => $user['email'] ?? null,
                'role'  => $user['role']  ?? null,
            ]) ?>,
        };
    </script>
</head>
<body class="min-h-[100dvh] bg-paper text-ink antialiased">

<!-- ─────────────────────────────────────────────────────────────────────────
     Visible JS diagnostic banner. Stays hidden when Alpine + dashboard.js boot
     cleanly. Shows the actual error message inline if anything throws so the
     user doesn't need to open DevTools.
     ───────────────────────────────────────────────────────────────────────── -->
<div id="decko-js-diag" style="display:none; position:fixed; top:0; left:0; right:0; z-index:9999; background:#7c2d12; color:#fff; padding:10px 14px; font:13px/1.5 'JetBrains Mono', ui-monospace, monospace; box-shadow:0 6px 18px rgba(0,0,0,0.2)">
    <span id="decko-js-diag-msg"></span>
    <button onclick="this.parentElement.style.display='none'" style="float:right; background:transparent; border:1px solid rgba(255,255,255,0.4); color:#fff; padding:2px 8px; cursor:pointer; font:inherit">close</button>
</div>
<script>
(function () {
    function show(msg) {
        var el  = document.getElementById('decko-js-diag');
        var box = document.getElementById('decko-js-diag-msg');
        if (!el || !box) return;
        // Only overwrite if empty so the first error wins.
        if (box.textContent.trim() === '') box.textContent = msg;
        el.style.display = 'block';
    }
    window.addEventListener('error', function (e) {
        show('JS error: ' + e.message + '  @ ' + (e.filename || '?') + ':' + (e.lineno || '?'));
    });
    window.addEventListener('unhandledrejection', function (e) {
        show('Promise rejected: ' + (e.reason && e.reason.message ? e.reason.message : e.reason));
    });
    // After 1.8s, check whether Alpine actually mounted and the dashboard state exists.
    setTimeout(function () {
        var root = document.querySelector('[x-data]');
        if (typeof window.Alpine === 'undefined') {
            show('Alpine.js did not load (window.Alpine missing). Could not reach cdn.jsdelivr.net — check your network/firewall.');
        } else if (typeof window.dashboard !== 'function') {
            show('dashboard.js did not load. Asset URL: ' + (window.DECKO && window.DECKO.url ? window.DECKO.url : '?') + 'assets/js/dashboard.js?v=<?= $assetVersion ?>');
        } else if (root && !root._x_dataStack) {
            show('Alpine loaded but failed to bind. Try Ctrl+Shift+R once more. If it persists, your browser may have a stale Alpine plugin or extension blocking it.');
        }
    }, 1800);
})();
</script>

<?= $content ?>
</body>
</html>
