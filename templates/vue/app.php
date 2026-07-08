<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Location Chantier' ?></title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <?php
    // Déterminer l'environnement
    $isDev = ($_ENV['APP_ENV'] ?? 'dev') === 'dev';
    ?>

    <?php if ($isDev): ?>
        <!-- ✅ Mode développement : utilise Vite Dev Server -->
        <script type="module" src="http://localhost:5173/@vite/client"></script>
        <script type="module" src="http://localhost:5173/public/assets/js/app.js"></script>
    <?php else: ?>
        <!-- ✅ Mode production : utilise les fichiers buildés -->
        <?php
        // Lire le fichier manifest.json généré par Vite
        $manifestPath = __DIR__ . '/../../public/dist/.vite/manifest.json';
        $manifest = [];

        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
        }

        // Trouver les fichiers buildés
        $jsFile = null;
        $cssFile = null;

        if (!empty($manifest)) {
            // Chercher le fichier JS principal
            foreach ($manifest as $key => $value) {
                if (strpos($key, 'app.js') !== false) {
                    $jsFile = $value['file'] ?? null;
                    if (isset($value['css']) && !empty($value['css'])) {
                        $cssFile = $value['css'][0] ?? null;
                    }
                    break;
                }
            }
        }

        // Fallback : chercher manuellement les fichiers dans dist/assets
        if (!$jsFile) {
            $distPath = __DIR__ . '/../../public/dist/assets/';
            if (is_dir($distPath)) {
                $files = scandir($distPath);
                foreach ($files as $file) {
                    if (strpos($file, '.js') !== false && strpos($file, '.js.map') === false) {
                        $jsFile = 'assets/' . $file;
                    }
                    if (strpos($file, '.css') !== false && strpos($file, '.css.map') === false) {
                        $cssFile = 'assets/' . $file;
                    }
                }
            }
        }
        ?>

        <?php if ($cssFile): ?>
    <link rel="stylesheet" href="/dist/<?= $cssFile ?>">
    <?php endif; ?>

    <?php if ($jsFile): ?>
        <script type="module" src="/dist/<?= $jsFile ?>"></script>
    <?php else: ?>
        <!-- Fallback : si les fichiers buildés ne sont pas trouvés -->
        <p style="color: red;">⚠️ Fichiers buildés non trouvés. Lancez <code>npm run build</code></p>
    <?php endif; ?>
    <?php endif; ?>

    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        #app {
            min-height: 100vh;
        }

        .sidebar-heading {
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
<div id="app">
    <!-- Vue va monter ici -->
    <div class="d-flex justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="text-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Chargement...</span>
            </div>
            <p class="mt-3 text-muted">Chargement de l'application...</p>
        </div>
    </div>
</div>
</body>
</html>