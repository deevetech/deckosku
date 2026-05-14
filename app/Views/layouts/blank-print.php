<?php
/** @var string $content */
/** @var string $appName */
use App\Core\View;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= View::e($appName) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Geist', 'system-ui', 'sans-serif'],
                        mono: ['Geist Mono', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        ink:    { DEFAULT: '#18181b', soft: '#52525b', muted: '#a1a1aa' },
                        accent: { DEFAULT: '#047857', soft: '#d1fae5' },
                        canvas: '#fafaf9',
                        line:   { DEFAULT: '#e4e4e7', soft: '#f4f4f5' },
                    },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="<?= View::asset('css/app.css') ?>">
    <style>
        @page { size: A4; margin: 14mm; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; }
        }
    </style>
</head>
<body class="min-h-[100dvh] bg-canvas text-ink antialiased">
<?= $content ?>
</body>
</html>
