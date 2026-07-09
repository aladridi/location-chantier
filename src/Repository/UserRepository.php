<?php
namespace App\Repository;

use App\Entity\User;
use App\Core\Repository\AbstractRepository;

class UserRepository extends AbstractRepository
{
    protected function initialize(): void
    {
        $this->tableName = 'users';
        $this->entityClass = User::class;
    }

    /**
     * Hydrate un utilisateur en s'assurant que les rôles sont bien un tableau
     */
    protected function hydrate(array $data): object
    {
        // Décoder les rôles si c'est du JSON
        if (isset($data['roles']) && is_string($data['roles'])) {
            $data['roles'] = json_decode($data['roles'], true);
            if (!is_array($data['roles'])) {
                $data['roles'] = ['ROLE_USER'];
            }
        } elseif (!isset($data['roles']) || !is_array($data['roles'])) {
            $data['roles'] = ['ROLE_USER'];
        }

        return parent::hydrate($data);
    }

    /**
     * Extrait les données en encodant les rôles en JSON
     */
    protected function extractData(object $entity): array
    {
        $data = parent::extractData($entity);

        // Encoder les rôles en JSON pour la base de données
        if (isset($data['roles']) && is_array($data['roles'])) {
            $data['roles'] = json_encode($data['roles']);
        }

        return $data;
    }

    public function findByEmail(string $email): ?User
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE email = :email";
        $result = $this->db->query($sql, ['email' => strtolower(trim($email))]);

        if (empty($result)) {
            return null;
        }

        return $this->hydrate($result[0]);
    }

    public function findByUsername(string $username): ?User
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE username = :username";
        $result = $this->db->query($sql, ['username' => $username]);

        if (empty($result)) {
            return null;
        }

        return $this->hydrate($result[0]);
    }

    public function findAllActive(): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE active = 1 ORDER BY username";
        $results = $this->db->query($sql);
        return array_map([$this, 'hydrate'], $results);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $sql = "UPDATE {$this->tableName} SET password_hash = :password, updated_at = NOW() WHERE id = :id";
        $this->db->execute($sql, [
            'id' => $id,
            'password' => $passwordHash
        ]);
    }

    public function updateLastLogin(int $id): void
    {
        $sql = "UPDATE {$this->tableName} SET last_login = NOW() WHERE id = :id";
        $this->db->execute($sql, ['id' => $id]);
    }

    public function toggleActive(int $id, bool $active): void
    {
        $sql = "UPDATE {$this->tableName} SET active = :active, updated_at = NOW() WHERE id = :id";
        $this->db->execute($sql, [
            'id' => $id,
            'active' => $active ? 1 : 0
        ]);
    }

    public function createUser(string $username, string $email, string $passwordHash, array $roles = ['ROLE_USER']): User
    {
        $user = new User($username, $email, $passwordHash, $roles);
        $this->save($user);
        return $user;
    }
}