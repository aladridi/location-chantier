<?php
return [
    'dsn' => sprintf(
        '%s:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_DRIVER'] ?? 'mysql',
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_PORT'] ?? '3306',
        $_ENV['DB_NAME'] ?? 'location_chantier'
    ),
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASSWORD'] ?? '',
];