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
        $limit = max(1, min(100, $limit));
        $sql = "SELECT * FROM {$this->tableName}
                WHERE first_name LIKE ? OR last_name LIKE ?
                OR CONCAT(first_name, ' ', last_name) LIKE ?
                ORDER BY last_name, first_name
                LIMIT {$limit}";
        $term = '%' . trim($search) . '%';
        return $this->hydrateMultiple($this->db->query($sql, [$term, $term, $term]));
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
        return $this->hydrateMultiple($this->getTopClientStatistics($limit));
    }

    /** Résultats agrégés conservés pour l'API de statistiques. */
    public function getTopClientStatistics(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        $sql = "SELECT c.*, totals.rental_count, totals.total_spent
                FROM {$this->tableName} c
                JOIN (
                    SELECT client_id, COUNT(*) AS rental_count,
                           COALESCE(SUM(total_price), 0) AS total_spent
                    FROM rentals GROUP BY client_id
                ) totals ON totals.client_id = c.id
                ORDER BY totals.rental_count DESC, c.id ASC
                LIMIT {$limit}";
        return $this->db->query($sql);
    }

    /**
     * Statistiques des clients
     */
    public function getStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COALESCE(SUM(company IS NOT NULL AND company != ''), 0) as companies,
                    COALESCE(SUM(company IS NULL OR company = ''), 0) as individuals,
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
        [$where, $params] = $this->buildSearchConditions($criteria);
        $sql = "SELECT * FROM {$this->tableName}" . $where;
        if ($pagination) {
            $order = [];
            foreach ($pagination->getOrder() as $field => $direction) {
                if (!in_array($field, ['id', 'first_name', 'last_name', 'email', 'company', 'city', 'created_at', 'updated_at'], true)) {
                    throw new \InvalidArgumentException('Champ de tri client invalide');
                }
                $direction = strtoupper($direction);
                if (!in_array($direction, ['ASC', 'DESC'], true)) {
                    throw new \InvalidArgumentException('Sens de tri client invalide');
                }
                $order[] = $field . ' ' . $direction;
            }
            if ($order) {
                $sql .= ' ORDER BY ' . implode(', ', $order);
            }
            if ($pagination->getLimit() !== null) {
                $sql .= ' LIMIT ' . max(1, min(100, $pagination->getLimit()));
                $sql .= ' OFFSET ' . max(0, $pagination->getOffset() ?? 0);
            }
        }
        return $this->hydrateMultiple($this->db->query($sql, $params));
    }

    /** Même filtrage pour le total et pour les lignes de la liste. */
    public function count(array $criteria = []): int
    {
        [$where, $params] = $this->buildSearchConditions($criteria);
        $result = $this->db->query("SELECT COUNT(*) AS total FROM {$this->tableName}" . $where, $params);
        return (int) ($result[0]['total'] ?? 0);
    }

    private function buildSearchConditions(array $criteria): array
    {
        $conditions = [];
        $params = [];
        foreach ($criteria as $field => $value) {
            if ($field === 'search' && $value !== null && $value !== '') {
                $conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR CONCAT(first_name, ' ', last_name) LIKE ? OR email LIKE ? OR company LIKE ?)";
                array_push($params, ...array_fill(0, 5, '%' . $value . '%'));
            } elseif (in_array($field, ['email', 'company', 'city'], true) && !is_array($value)) {
                $conditions[] = $field . ' LIKE ?';
                $params[] = '%' . $value . '%';
            } elseif ($field === 'has_company') {
                $conditions[] = $value
                    ? "(company IS NOT NULL AND company != '')"
                    : "(company IS NULL OR company = '')";
            } elseif (in_array($field, ['id', 'first_name', 'last_name', 'phone', 'address', 'postal_code'], true) && !is_array($value)) {
                $conditions[] = $field . ' = ?';
                $params[] = $value;
            } else {
                throw new \InvalidArgumentException('Filtre client invalide : ' . $field);
            }
        }
        return [$conditions ? ' WHERE ' . implode(' AND ', $conditions) : '', $params];
    }
}
