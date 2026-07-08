<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$dbConfig = require __DIR__ . '/../config/database.php';
$pdo = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password']);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$migrationsPath = realpath(__DIR__ . '/../migrations');
$sqlFiles = glob($migrationsPath . '/*.sql');
sort($sqlFiles);
foreach ($sqlFiles as $file) {
    echo "Exécution de " . basename($file) . "...\n";
    $sql = file_get_contents($file);
    $pdo->exec($sql);
}

echo "Migrations terminées !\n";