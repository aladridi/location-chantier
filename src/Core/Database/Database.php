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
            // ✅ Message d'erreur plus détaillé
            $errorMsg = "Database connection failed: " . $e->getMessage();
            $errorMsg .= "\n\n📝 Vérifiez votre configuration :";
            $errorMsg .= "\n   - DSN: $dsn";
            $errorMsg .= "\n   - Utilisateur: $username";

            // Conseils spécifiques
            if (strpos($e->getMessage(), 'No such file or directory') !== false) {
                $errorMsg .= "\n\n🔧 Conseils :";
                $errorMsg .= "\n   1. Vérifiez que MySQL/MAMP est démarré";
                $errorMsg .= "\n   2. Vérifiez le socket MySQL dans votre .env";
                $errorMsg .= "\n   3. Essayez avec DB_HOST=127.0.0.1";
                $errorMsg .= "\n   4. Sur MAMP, le socket est souvent : /Applications/MAMP/tmp/mysql/mysql.sock";
            }

            throw new \RuntimeException($errorMsg);
        }
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
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