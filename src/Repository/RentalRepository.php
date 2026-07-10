<?php
namespace App\Repository;

use App\Entity\Rental;
use App\Entity\Enum\RentalStatus;
use App\Core\Repository\AbstractRepository;
use App\Core\Repository\Criteria\Criteria;

class RentalRepository extends AbstractRepository implements RentalRepositoryInterface
{
    protected function initialize(): void
    {
        $this->tableName = 'rentals';
        $this->entityClass = Rental::class;
    }

    /**
     * Trouve les locations actives (pending, active, overdue)
     */
    public function findActive(): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE status IN ('pending', 'active', 'overdue')
                ORDER BY start_date";

        $results = $this->db->query($sql);
        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve les locations en retard (statut 'overdue')
     */
    public function findOverdue(): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE status = 'overdue'
                ORDER BY end_date";

        $results = $this->db->query($sql);
        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve les locations retournées
     */
    public function findReturned(): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE status = 'returned'
                ORDER BY returned_at DESC";

        $results = $this->db->query($sql);
        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve les locations par statut
     */
    public function findByStatus(RentalStatus $status): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE status = :status 
                ORDER BY start_date DESC";

        $results = $this->db->query($sql, ['status' => $status->value]);
        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve les locations par client
     */
    public function findByClient(int $clientId): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE client_id = :client_id 
                ORDER BY start_date DESC";

        $results = $this->db->query($sql, ['client_id' => $clientId]);
        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve les locations par équipement
     */
    public function findByEquipment(int $equipmentId): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE equipment_id = :equipment_id 
                ORDER BY start_date DESC";

        $results = $this->db->query($sql, ['equipment_id' => $equipmentId]);
        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve les locations dans une période
     */
    public function findByPeriod(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE (
                    (start_date BETWEEN :start AND :end)
                    OR (end_date BETWEEN :start AND :end)
                    OR (start_date <= :start AND end_date >= :end)
                )
                ORDER BY start_date";

        $results = $this->db->query($sql, [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve les locations d'un client dans une période
     */
    public function findByClientAndPeriod(int $clientId, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE client_id = :client_id
                AND (
                    (start_date BETWEEN :start AND :end)
                    OR (end_date BETWEEN :start AND :end)
                    OR (start_date <= :start AND end_date >= :end)
                )
                ORDER BY start_date";

        $results = $this->db->query($sql, [
            'client_id' => $clientId,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        return $this->hydrateMultiple($results);
    }

    /**
     * Recherche avancée de locations
     */
    public function search(array $criteria, ?Criteria $pagination = null): array
    {
        $sql = "SELECT r.*, 
                c.first_name, c.last_name, c.email,
                e.name as equipment_name
                FROM {$this->tableName} r
                LEFT JOIN clients c ON c.id = r.client_id
                LEFT JOIN equipment e ON e.id = r.equipment_id";

        $params = [];
        $conditions = [];

        if (!empty($criteria['client_id'])) {
            $conditions[] = "r.client_id = ?";
            $params[] = $criteria['client_id'];
        }

        if (!empty($criteria['equipment_id'])) {
            $conditions[] = "r.equipment_id = ?";
            $params[] = $criteria['equipment_id'];
        }

        if (!empty($criteria['status'])) {
            if (is_array($criteria['status'])) {
                $placeholders = implode(',', array_fill(0, count($criteria['status']), '?'));
                $conditions[] = "r.status IN ({$placeholders})";
                $params = array_merge($params, $criteria['status']);
            } else {
                $conditions[] = "r.status = ?";
                $params[] = $criteria['status'];
            }
        }

        if (!empty($criteria['start_date_from'])) {
            $conditions[] = "r.start_date >= ?";
            $params[] = $criteria['start_date_from']->format('Y-m-d H:i:s');
        }

        if (!empty($criteria['start_date_to'])) {
            $conditions[] = "r.start_date <= ?";
            $params[] = $criteria['start_date_to']->format('Y-m-d H:i:s');
        }

        if (!empty($criteria['end_date_from'])) {
            $conditions[] = "r.end_date >= ?";
            $params[] = $criteria['end_date_from']->format('Y-m-d H:i:s');
        }

        if (!empty($criteria['end_date_to'])) {
            $conditions[] = "r.end_date <= ?";
            $params[] = $criteria['end_date_to']->format('Y-m-d H:i:s');
        }

        if (isset($criteria['min_price'])) {
            $conditions[] = "r.total_price >= ?";
            $params[] = $criteria['min_price'];
        }

        if (isset($criteria['max_price'])) {
            $conditions[] = "r.total_price <= ?";
            $params[] = $criteria['max_price'];
        }

        if (!empty($criteria['client_name'])) {
            $conditions[] = "(c.first_name LIKE ? OR c.last_name LIKE ?)";
            $search = "%{$criteria['client_name']}%";
            $params[] = $search;
            $params[] = $search;
        }

        if (!empty($criteria['equipment_name'])) {
            $conditions[] = "e.name LIKE ?";
            $params[] = "%{$criteria['equipment_name']}%";
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        if ($pagination) {
            if ($pagination->getOrder()) {
                $orderClauses = [];
                foreach ($pagination->getOrder() as $field => $direction) {
                    $orderClauses[] = "r.{$field} {$direction}";
                }
                $sql .= " ORDER BY " . implode(', ', $orderClauses);
            } else {
                $sql .= " ORDER BY r.start_date DESC";
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

    /**
     * Statistiques des locations avec le champ status
     */
    public function getStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_rentals,
                    COUNT(DISTINCT client_id) as unique_clients,
                    COUNT(DISTINCT equipment_id) as unique_equipment,
                    SUM(total_price) as total_revenue,
                    AVG(total_price) as avg_rental_price,
                    AVG(DATEDIFF(end_date, start_date)) as avg_duration_days,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue,
                    SUM(CASE WHEN status = 'returned' THEN 1 ELSE 0 END) as returned,
                    SUM(CASE WHEN status = 'damaged' THEN 1 ELSE 0 END) as damaged
                FROM {$this->tableName}";

        $result = $this->db->query($sql);
        return $result[0] ?? [
            'total_rentals' => 0,
            'unique_clients' => 0,
            'unique_equipment' => 0,
            'total_revenue' => 0,
            'avg_rental_price' => 0,
            'avg_duration_days' => 0,
            'pending' => 0,
            'active' => 0,
            'overdue' => 0,
            'returned' => 0,
            'damaged' => 0,
        ];
    }

    /**
     * Revenus par mois (uniquement les locations terminées)
     */
    public function getMonthlyRevenue(int $months = 12): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(start_date, '%Y-%m') as month,
                    COUNT(*) as rentals_count,
                    SUM(total_price) as revenue,
                    AVG(total_price) as avg_revenue
                FROM {$this->tableName}
                WHERE status IN ('returned', 'damaged')
                AND start_date >= DATE_SUB(NOW(), INTERVAL :months MONTH)
                GROUP BY DATE_FORMAT(start_date, '%Y-%m')
                ORDER BY month DESC";

        return $this->db->query($sql, ['months' => $months]);
    }

    /**
     * Top équipements loués
     */
    public function getTopEquipment(int $limit = 10): array
    {
        $sql = "SELECT 
                    e.id,
                    e.name,
                    e.category,
                    COUNT(r.id) as rentals_count,
                    SUM(r.total_price) as total_revenue,
                    AVG(r.total_price) as avg_revenue
                FROM equipment e
                JOIN rentals r ON r.equipment_id = e.id
                GROUP BY e.id
                ORDER BY rentals_count DESC
                LIMIT :limit";

        return $this->db->query($sql, ['limit' => $limit]);
    }

    /**
     * Vérifie les conflits de disponibilité (exclut les locations retournées)
     */
    public function hasAvailabilityConflict(int $equipmentId, \DateTimeImmutable $start, \DateTimeImmutable $end, ?int $excludeRentalId = null): bool
    {
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->tableName} 
                WHERE equipment_id = :equipment_id
                AND status IN ('pending', 'active', 'overdue')
                AND (
                    (start_date <= :start AND end_date >= :start)
                    OR (start_date <= :end AND end_date >= :end)
                    OR (start_date >= :start AND end_date <= :end)
                )";

        $params = [
            'equipment_id' => $equipmentId,
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ];

        if ($excludeRentalId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeRentalId;
        }

        $result = $this->db->query($sql, $params);
        return (int) ($result[0]['count'] ?? 0) > 0;
    }

    /**
     * Met à jour les statuts en retard (active -> overdue)
     */
    public function updateOverdueStatus(): int
    {
        $sql = "UPDATE {$this->tableName} 
                SET status = 'overdue' 
                WHERE status = 'active' 
                AND end_date < NOW()";

        return $this->db->execute($sql);
    }

    /**
     * Récupère toutes les locations avec pagination
     */
    public function findAllWithPagination(int $limit = 20, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                ORDER BY created_at DESC 
                LIMIT :limit OFFSET :offset";

        $results = $this->db->query($sql, [
            'limit' => $limit,
            'offset' => $offset
        ]);
        return $this->hydrateMultiple($results);
    }

    /**
     * Compte le nombre total de locations
     */
    public function countAll(): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->tableName}";
        $result = $this->db->query($sql);
        return (int) ($result[0]['total'] ?? 0);
    }

    /**
     * Compte les locations par statut
     */
    public function countByStatus(RentalStatus $status): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->tableName} WHERE status = :status";
        $result = $this->db->query($sql, ['status' => $status->value]);
        return (int) ($result[0]['total'] ?? 0);
    }
}