<?php
namespace App\Core\Database;

use PDO;
use PDOException;

class Database implements DatabaseInterface
{
    private PDO $pdo;

    public function __construct(string $dsn, string $username, string $password)
    {
        try {
            $this->pdo = new PDO($dsn, $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new \RuntimeException("Database connection failed: " . $e->getMessage());
        }
    }

    public function query(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->pdo->prepare($sql);

            // ✅ S'assurer que les paramètres sont bien convertis
            $processedParams = [];
            foreach ($params as $key => $value) {
                if ($value instanceof \DateTimeImmutable) {
                    $processedParams[$key] = $value->format('Y-m-d H:i:s');
                } elseif ($value instanceof \App\Entity\Category) {
                    $processedParams[$key] = $value->getSlug();
                } elseif (is_object($value) && method_exists($value, '__toString')) {
                    $processedParams[$key] = (string) $value;
                } elseif (is_object($value) && method_exists($value, 'getId')) {
                    $processedParams[$key] = $value->getId();
                } elseif (is_bool($value)) {
                    $processedParams[$key] = $value ? 1 : 0;
                } elseif (is_array($value)) {
                    $processedParams[$key] = json_encode($value);
                } else {
                    $processedParams[$key] = $value;
                }
            }

            $stmt->execute($processedParams);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new \RuntimeException("Query failed: " . $e->getMessage() . " - SQL: " . $sql);
        }
    }

    public function execute(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->pdo->prepare($sql);

            // ✅ S'assurer que les paramètres sont bien convertis
            $processedParams = [];
            foreach ($params as $key => $value) {
                if ($value instanceof \DateTimeImmutable) {
                    $processedParams[$key] = $value->format('Y-m-d H:i:s');
                } elseif ($value instanceof \App\Entity\Category) {
                    $processedParams[$key] = $value->getSlug();
                } elseif (is_object($value) && method_exists($value, '__toString')) {
                    $processedParams[$key] = (string) $value;
                } elseif (is_object($value) && method_exists($value, 'getId')) {
                    $processedParams[$key] = $value->getId();
                } elseif (is_bool($value)) {
                    $processedParams[$key] = $value ? 1 : 0;
                } elseif (is_array($value)) {
                    $processedParams[$key] = json_encode($value);
                } else {
                    $processedParams[$key] = $value;
                }
            }

            $stmt->execute($processedParams);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new \RuntimeException("Execute failed: " . $e->getMessage() . " - SQL: " . $sql);
        }
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    public function commit(): void
    {
        $this->pdo->commit();
    }

    public function rollback(): void
    {
        $this->pdo->rollBack();
    }
}