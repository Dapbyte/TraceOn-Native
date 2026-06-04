<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars($pageTitle ?? 'TraceOn', ENT_QUOTES, 'UTF-8') ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <script src="https://cdn.jsdelivr.net/npm/@iconify/iconify@3/dist/iconify.min.js" defer></script>

    <!-- Styles -->
    <link rel="stylesheet" href="/css/tokens.css">
    <link rel="stylesheet" href="/css/base.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/layouts.css">
</head>
<body>
    <div class="auth-layout">
        <div class="auth-box">
            <?= $content ?? '' ?>
        </div>
    </div>

    <!-- Toast container -->
    <div class="toast-container" id="toast-container" aria-live="polite" aria-atomic="false"></div>

    <script type="module" src="/js/main.js"></script>
    <?php if (isset($pageScript)): ?>
    <script type="module" src="<?= htmlspecialchars($pageScript, ENT_QUOTES, 'UTF-8') ?>"></script>
    <?php endif; ?>
</body>
</html>
