<?php
/** @var string $content */
/** @var string $appName */
use App\Core\View;
$assetVersion = '4';
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#faf8f5">
    <title><?= View::e($appName) ?> — sign in</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='6' fill='%2318171c'/%3E%3Ctext x='16' y='22' text-anchor='middle' font-family='Geist, system-ui, sans-serif' font-weight='600' font-size='15' fill='%23faf8f5'%3ED%3C/text%3E%3C/svg%3E">
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Geist', 'system-ui', 'sans-serif'],
                        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
                    },
                    colors: {
                        paper:        '#faf8f5',
                        'paper-soft': '#f5f2eb',
                        surface:      '#ffffff',
                        ink:          { DEFAULT: '#18171c', soft: '#43424a', mute: '#6b6873', dim: '#9a96a0' },
                        line:         { DEFAULT: '#e8e2d6', soft: '#f0ebde', strong: '#d8d2c4' },
                        ember:        { DEFAULT: '#c2410c', soft: '#f4d8c3' },
                    },
                    letterSpacing: { tightest: '-0.022em' },
                },
            },
        };
    </script>
    <link rel="stylesheet" href="<?= View::asset('css/app.css?v=' . $assetVersion) ?>">
</head>
<body class="min-h-[100dvh] bg-paper text-ink antialiased">
<?= $content ?>
</body>
</html>
