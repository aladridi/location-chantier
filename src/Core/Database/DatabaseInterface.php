<?php
namespace App\Core\Database;

interface DatabaseInterface
{
    public function query(string $sql, array $params = []): array;
    public function execute(string $sql, array $params = []): int;
    public function lastInsertId(): string;
    public function beginTransaction(): void;
    public function commit(): void;
    public function rollback(): void;
}