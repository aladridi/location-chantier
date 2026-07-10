<?php
namespace App\Repository;

use App\Entity\Client;
use App\Core\Repository\AbstractRepository;
use App\Core\Repository\Criteria\Criteria;  // ✅ Import correct

class ClientRepository extends AbstractRepository implements ClientRepositoryInterface
{
    protected function initialize(): void
    {
        $this->tableName = 'clients';
        $this->entityClass = Client::class;
    }

    /**
     * {@inheritdoc}
     * @return Client|null
     */
    public function find(int $id): ?object
    {
        return parent::find($id);
    }

    /**
     * {@inheritdoc}
     * @return Client|null
     */
    public function findOneBy(array $criteria): ?object
    {
        return parent::findOneBy($criteria);
    }

    /**
     * {@inheritdoc}
     * @return Client[]
     */
    public function findBy(array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): array
    {
        return parent::findBy($criteria, $orderBy, $limit, $offset);
    }

    /**
     * Trouve un client par email
     */
    public function findByEmail(string $email): ?Client
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE email = :email";
        $result = $this->db->query($sql, ['email' => strtolower(trim($email))]);

        if (empty($result)) {
            return null;
        }

        return $this->hydrate($result[0]);
    }

    /**
     * Recherche des clients par nom
     */
    public function searchByName(string $search, int $limit = 10): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE first_name LIKE :search 
                OR last_name LIKE :search 
                OR CONCAT(first_name, ' ', last_name) LIKE :search
                ORDER BY last_name, first_name
                LIMIT :limit";

        $searchTerm = "%{$search}%";
        $results = $this->db->query($sql, [
            'search' => $searchTerm,
            'limit' => $limit
        ]);
        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve les clients actifs (ayant des locations en cours)
     */
    public function findActiveClients(): array
    {
        $sql = "SELECT DISTINCT c.* FROM {$this->tableName} c
                JOIN rentals r ON r.client_id = c.id
                WHERE r.status IN ('pending', 'active', 'overdue')
                ORDER BY c.last_name, c.first_name";

        $results = $this->db->query($sql);
        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve les meilleurs clients
     */
    public function findTopClients(int $limit = 10): array
    {
        $sql = "SELECT 
                    c.*, 
                    COUNT(r.id) as rental_count, 
                    SUM(r.total_price) as total_spent
                FROM {$this->tableName} c
                JOIN rentals r ON r.client_id = c.id
                GROUP BY c.id
                ORDER BY rental_count DESC
                LIMIT :limit";

        $results = $this->db->query($sql, ['limit' => $limit]);
        return $this->hydrateMultiple($results);
    }

    /**
     * Statistiques des clients
     */
    public function getStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(company IS NOT NULL AND company != '') as companies,
                    SUM(company IS NULL OR company = '') as individuals,
                    COUNT(DISTINCT city) as cities
                FROM {$this->tableName}";

        $result = $this->db->query($sql);
        return $result[0] ?? [
            'total' => 0,
            'companies' => 0,
            'individuals' => 0,
            'cities' => 0,
        ];
    }

    /**
     * Vérifie si un email existe déjà
     */
    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->tableName} WHERE email = :email";
        $params = ['email' => strtolower(trim($email))];

        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }

        $result = $this->db->query($sql, $params);
        return (int) ($result[0]['count'] ?? 0) > 0;
    }

    /**
     * Récupère les locations d'un client
     */
    public function getClientRentals(int $clientId): array
    {
        $sql = "SELECT * FROM rentals 
                WHERE client_id = :client_id 
                ORDER BY created_at DESC";

        $results = $this->db->query($sql, ['client_id' => $clientId]);
        return $results;
    }

    /**
     * Compte les locations actives d'un client
     */
    public function getActiveRentalsCount(int $clientId): int
    {
        $sql = "SELECT COUNT(*) as count FROM rentals 
                WHERE client_id = :client_id 
                AND status IN ('pending', 'active', 'overdue')";

        $result = $this->db->query($sql, ['client_id' => $clientId]);
        return (int) ($result[0]['count'] ?? 0);
    }

    /**
     * Recherche avancée de clients
     */
    public function search(array $criteria, ?Criteria $pagination = null): array
    {
        $sql = "SELECT * FROM {$this->tableName}";
        $params = [];
        $conditions = [];

        if (!empty($criteria['search'])) {
            $conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $search = "%{$criteria['search']}%";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($criteria['email'])) {
            $conditions[] = "email LIKE ?";
            $params[] = "%{$criteria['email']}%";
        }

        if (!empty($criteria['company'])) {
            $conditions[] = "company LIKE ?";
            $params[] = "%{$criteria['company']}%";
        }

        if (!empty($criteria['city'])) {
            $conditions[] = "city LIKE ?";
            $params[] = "%{$criteria['city']}%";
        }

        if (isset($criteria['has_company'])) {
            if ($criteria['has_company']) {
                $conditions[] = "company IS NOT NULL AND company != ''";
            } else {
                $conditions[] = "(company IS NULL OR company = '')";
            }
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        if ($pagination) {
            if ($pagination->getOrder()) {
                $orderClauses = [];
                foreach ($pagination->getOrder() as $field => $direction) {
                    $orderClauses[] = "{$field} {$direction}";
                }
                $sql .= " ORDER BY " . implode(', ', $orderClauses);
            }

            if ($pagination->getLimit() !== null) {
                $sql .= " LIMIT " . $pagination->getLimit();
                if ($pagination->getOffset() !== null) {
                    $sql .= " OFFSET " . $pagination->getOffset();
                }
            }
        }

        $results = $this->db->query($sql, $params);
        return $this->hydrateMultiple($results);
    }
}