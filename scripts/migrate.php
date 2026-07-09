<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    // ✅ Récupérer toutes les variables d'environnement
    $driver = $_ENV['DB_DRIVER'] ?? 'mysql';
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $database = $_ENV['DB_NAME'] ?? 'location_chantier';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    $socket = $_ENV['DB_SOCKET'] ?? null;

    // ✅ Détection de l'environnement
    $isMac = strpos(PHP_OS, 'DAR') !== false || strpos(PHP_OS, 'Darwin') !== false;
    $isWindows = strpos(PHP_OS, 'WIN') !== false || strpos(PHP_OS, 'Windows') !== false;

    // ✅ Construction du DSN adapté
    if ($socket && file_exists($socket)) {
        // Utiliser le socket si spécifié et existant
        $dsn = "$driver:unix_socket=$socket;dbname=$database;charset=utf8mb4";
        echo "🔌 Utilisation du socket: $socket\n";
    } elseif ($isMac && $host === 'localhost') {
        // Sur Mac, essayer le socket MAMP par défaut
        $mampSocket = '/Applications/MAMP/tmp/mysql/mysql.sock';
        if (file_exists($mampSocket)) {
            $dsn = "$driver:unix_socket=$mampSocket;dbname=$database;charset=utf8mb4";
            echo "🍎 Utilisation du socket MAMP: $mampSocket\n";
        } else {
            $dsn = "$driver:host=$host;port=$port;dbname=$database;charset=utf8mb4";
            echo "🍎 Utilisation de $host:$port\n";
        }
    } else {
        $dsn = "$driver:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        echo "💻 Utilisation de $host:$port\n";
    }

    echo "📦 Connexion à la base de données...\n";
    echo "   Base: $database\n";
    echo "   Utilisateur: $username\n";

    // ✅ Connexion
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connexion réussie !\n\n";

    // ✅ Lire les fichiers SQL
    $migrationDir = __DIR__ . '/../migrations';
    if (!is_dir($migrationDir)) {
        echo "⚠️  Dossier migrations/ non trouvé. Création...\n";
        mkdir($migrationDir, 0755, true);
        echo "✅ Dossier créé.\n";
    }

    $sqlFiles = glob($migrationDir . '/*.sql');
    sort($sqlFiles);

    if (empty($sqlFiles)) {
        echo "⚠️  Aucun fichier SQL trouvé dans le dossier migrations/\n";
        echo "   Créez un fichier .sql dans le dossier migrations/\n";
        exit(0);
    }

    foreach ($sqlFiles as $file) {
        echo "📄 Exécution de " . basename($file) . "...\n";
        $sql = file_get_contents($file);

        if ($sql === false) {
            echo "  ❌ Impossible de lire le fichier\n";
            continue;
        }

        // ✅ Séparer les requêtes
        $queries = array_filter(
            array_map('trim', explode(';', $sql)),
            fn($q) => !empty($q) && !str_starts_with($q, '--')
        );

        foreach ($queries as $query) {
            try {
                $pdo->exec($query);
                echo "  ✅ Requête exécutée\n";
            } catch (PDOException $e) {
                $errorMsg = strtolower($e->getMessage());
                if (strpos($errorMsg, 'already exists') !== false ||
                    strpos($errorMsg, 'duplicate') !== false) {
                    echo "  ⚠️  Table déjà existante, ignorée.\n";
                } else {
                    throw $e;
                }
            }
        }
        echo "  ✅ Terminé.\n\n";
    }

    echo "\n✅ Migrations terminées avec succès !\n";

    // ✅ Vérifier la table users
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "\n📋 Table 'users' créée avec succès.\n";

            $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
            $count = $stmt->fetchColumn();
            echo "   Nombre d'utilisateurs: $count\n";

            if ($count == 0) {
                echo "   ⚠️  Aucun utilisateur trouvé.\n";
                echo "   💡 Exécutez: php scripts/create_admin.php\n";
            }
        }
    } catch (PDOException $e) {
        // Ignorer si la table n'existe pas encore
    }

} catch (PDOException $e) {
    echo "\n❌ Erreur de connexion : " . $e->getMessage() . "\n";

    // ✅ Conseils adaptés à l'environnement
    $isMac = strpos(PHP_OS, 'DAR') !== false || strpos(PHP_OS, 'Darwin') !== false;
    $isWindows = strpos(PHP_OS, 'WIN') !== false || strpos(PHP_OS, 'Windows') !== false;

    echo "\n📝 Conseils de résolution :\n";

    if ($isMac) {
        echo "  🍎 Configuration Mac :\n";
        echo "  1. Vérifiez que MAMP est démarré (icône verte)\n";
        echo "  2. Vérifiez le port MySQL dans MAMP (Preferences > MySQL > Port)\n";
        echo "  3. Vérifiez le mot de passe root (généralement 'root')\n";
        echo "  4. Vérifiez que la base de données '$database' existe\n";
        echo "  5. Essayez avec DB_HOST=127.0.0.1\n";
        echo "  6. Ou utilisez DB_SOCKET=/Applications/MAMP/tmp/mysql/mysql.sock\n";
    } elseif ($isWindows) {
        echo "  💻 Configuration Windows :\n";
        echo "  1. Vérifiez que WAMP/XAMPP est démarré\n";
        echo "  2. Vérifiez le port MySQL (généralement 3306)\n";
        echo "  3. Vérifiez le mot de passe root (généralement vide ou 'root')\n";
        echo "  4. Vérifiez que la base de données '$database' existe\n";
    } else {
        echo "  🐧 Configuration Linux :\n";
        echo "  1. Vérifiez que MySQL est démarré: sudo systemctl status mysql\n";
        echo "  2. Vérifiez le port MySQL (généralement 3306)\n";
        echo "  3. Vérifiez le mot de passe root\n";
        echo "  4. Vérifiez que la base de données '$database' existe\n";
    }

    echo "\n   Exemple de fichier .env :\n";
    if ($isMac) {
        echo "   DB_HOST=127.0.0.1\n";
        echo "   DB_PORT=8889\n";
        echo "   DB_NAME=$database\n";
        echo "   DB_USER=root\n";
        echo "   DB_PASSWORD=root\n";
    } else {
        echo "   DB_HOST=localhost\n";
        echo "   DB_PORT=3306\n";
        echo "   DB_NAME=$database\n";
        echo "   DB_USER=root\n";
        echo "   DB_PASSWORD=\n";
    }

    exit(1);
}