<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Location Chantier' ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Vite Dev Server -->
    <?php if ($_ENV['APP_ENV'] === 'dev'): ?>
        <script type="module" src="http://localhost:5173/@vite/client"></script>
        <script type="module" src="http://localhost:5173/public/assets/js/app.js"></script>
    <?php else: ?>
        <!-- Production Build -->
    <link rel="stylesheet" href="/dist/assets/app.css">
        <script type="module" src="/dist/assets/app.js"></script>
    <?php endif; ?>
</head>
<body>
<div id="app"></div>
</body>
</html>