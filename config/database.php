<?php
// Détection de l'environnement
$isMac = strpos(PHP_OS, 'DAR') !== false || strpos(PHP_OS, 'Darwin') !== false;

// Configuration par défaut
$config = [
    'driver' => $_ENV['DB_DRIVER'] ?? 'mysql',
    'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? '3306',
    'database' => $_ENV['DB_NAME'] ?? 'location_chantier',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
];

if ($isMac && !isset($_ENV['DB_SOCKET'])) {
    $mampSocket = '/Applications/MAMP/tmp/mysql/mysql.sock';
    if (file_exists($mampSocket)) {
        $config['socket'] = $mampSocket;
        // Pas besoin de host/port si on utilise un socket
        unset($config['host']);
        unset($config['port']);
    }
}

// ✅ Si un socket est spécifié dans .env, l'utiliser
if (isset($_ENV['DB_SOCKET']) && !empty($_ENV['DB_SOCKET'])) {
    $config['socket'] = $_ENV['DB_SOCKET'];
    unset($config['host']);
    unset($config['port']);
}

return $config;