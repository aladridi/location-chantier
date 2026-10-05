<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Service\Auth\PasswordHasher;

$hasher = new PasswordHasher();

$password = 'password';

echo $hasher->hash($password) . PHP_EOL;