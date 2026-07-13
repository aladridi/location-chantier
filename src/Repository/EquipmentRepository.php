<?php
namespace App\Repository;

use App\Entity\Equipment;
use App\Entity\Category;
use App\Entity\Enum\EquipmentCategory;
use App\Core\Repository\AbstractRepository;
use App\Core\Repository\Criteria\Criteria;

class EquipmentRepository extends AbstractRepository implements EquipmentRepositoryInterface
{
    protected CategoryRepository $categoryRepository;
    protected function initialize(): void
    {
        $this->tableName = 'equipment';
        $this->entityClass = Equipment::class;
        $this->categoryRepository = new CategoryRepository($this->db);
    }

    /**
     * Trouve les équipements disponibles
     */
    public function findAvailable(): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE available = 1 ORDER BY name";
        $results = $this->db->query($sql);
        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve les équipements par catégorie
     */
    public function findByCategory(EquipmentCategory $category): array
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE category = :category ORDER BY name";
        $results = $this->db->query($sql, ['category' => $category->value]);
        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve les équipements nécessitant une maintenance
     */
    public function findNeedingMaintenance(int $daysThreshold = 90): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE (last_maintenance IS NULL 
                OR last_maintenance < DATE_SUB(NOW(), INTERVAL :days DAY))
                ORDER BY last_maintenance ASC";

        $results = $this->db->query($sql, ['days' => $daysThreshold]);
        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve les équipements les plus loués
     */
    public function findMostRented(int $limit = 10): array
    {
        $sql = "SELECT e.*, COUNT(r.id) as rental_count 
                FROM {$this->tableName} e
                LEFT JOIN rentals r ON r.equipment_id = e.id
                GROUP BY e.id
                ORDER BY rental_count DESC
                LIMIT :limit";

        $results = $this->db->query($sql, ['limit' => $limit]);
        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve les équipements disponibles dans une période donnée
     */
    public function findAvailableForPeriod(\DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $sql = "SELECT e.* FROM {$this->tableName} e
                WHERE e.available = 1
                AND NOT EXISTS (
                    SELECT 1 FROM rentals r 
                    WHERE r.equipment_id = e.id
                    AND r.status IN ('active', 'pending')
                    AND (
                        (r.start_date <= :start AND r.end_date >= :start)
                        OR (r.start_date <= :end AND r.end_date >= :end)
                        OR (r.start_date >= :start AND r.end_date <= :end)
                    )
                )
                ORDER BY e.name";

        $results = $this->db->query($sql, [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s'),
        ]);

        return $this->hydrateMultiple($results);
    }

    /**
     * Recherche d'équipements avec critères avancés
     */
    public function search(array $criteria, ?Criteria $pagination = null): array
    {
        $sql = "SELECT * FROM {$this->tableName}";
        $params = [];
        $conditions = [];

        // Critères de recherche
        if (!empty($criteria['name'])) {
            $conditions[] = "name LIKE ?";
            $params[] = "%{$criteria['name']}%";
        }

        if (!empty($criteria['category'])) {
            $conditions[] = "category = ?";
            $params[] = $criteria['category'];
        }

        if (isset($criteria['available'])) {
            $conditions[] = "available = ?";
            $params[] = $criteria['available'] ? 1 : 0;
        }

        if (!empty($criteria['needs_maintenance'])) {
            $conditions[] = "(last_maintenance IS NULL OR last_maintenance < DATE_SUB(NOW(), INTERVAL 90 DAY))";
        }

        if (!empty($criteria['min_rate'])) {
            $conditions[] = "daily_rate >= ?";
            $params[] = $criteria['min_rate'];
        }

        if (!empty($criteria['max_rate'])) {
            $conditions[] = "daily_rate <= ?";
            $params[] = $criteria['max_rate'];
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        // Pagination
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

    /**
     * Statistiques sur les équipements
     */
    public function getStatistics(): array
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(available = 1) as available,
                    SUM(available = 0) as rented,
                    COUNT(DISTINCT category) as categories,
                    AVG(daily_rate) as avg_daily_rate
                FROM {$this->tableName}";

        $result = $this->db->query($sql);
        return $result[0] ?? [
            'total' => 0,
            'available' => 0,
            'rented' => 0,
            'categories' => 0,
            'avg_daily_rate' => 0,
        ];
    }

    /**
     * Met à jour le statut de disponibilité
     */
    public function updateAvailability(int $id, bool $available): void
    {
        $sql = "UPDATE {$this->tableName} SET available = :available WHERE id = :id";
        $this->db->execute($sql, [
            'id' => $id,
            'available' => $available ? 1 : 0,
        ]);
    }

    /**
     * Enregistre une maintenance
     */
    public function recordMaintenance(int $id, \DateTimeImmutable $date): void
    {
        $sql = "UPDATE {$this->tableName} SET last_maintenance = :date WHERE id = :id";
        $this->db->execute($sql, [
            'id' => $id,
            'date' => $date->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Compte par catégorie
     */
    public function countByCategory(): array
    {
        $sql = "SELECT category, COUNT(*) as count 
                FROM {$this->tableName} 
                GROUP BY category 
                ORDER BY count DESC";

        return $this->db->query($sql);
    }

    protected function extractData(object $entity): array
    {
        $data = parent::extractData($entity);

        if (isset($data['category']) && $data['category'] instanceof \App\Entity\Category) {
            $data['category'] = $data['category']->getSlug();
        }

        if (isset($data['last_maintenance']) && $data['last_maintenance'] instanceof \DateTimeImmutable) {
            $data['last_maintenance'] = $data['last_maintenance']->format('Y-m-d H:i:s');
        }

        return $data;
    }

    public function save(object $entity): void
    {
        if (!$entity instanceof Equipment) {
            throw new \InvalidArgumentException('Entity must be instance of Equipment');
        }

        $equipment = $entity;

        $category = $equipment->getCategory();
        $categorySlug = $category ? $category->getSlug() : null;
        $id = $equipment->getId();

        if ($id) {
            $sql = "UPDATE {$this->tableName} SET 
                        name = :name,
                        category = :category,
                        serial_number = :serial_number,
                        daily_rate = :daily_rate,
                        available = :available,
                        last_maintenance = :last_maintenance
                    WHERE id = :id";

            $this->db->execute($sql, [
                'id' => $id,
                'name' => $equipment->getName(),
                'category' => $categorySlug,
                'serial_number' => $equipment->getSerialNumber(),
                'daily_rate' => $equipment->getDailyRate(),
                'available' => $equipment->isAvailable() ? 1 : 0,
                'last_maintenance' => $equipment->getLastMaintenance()?->format('Y-m-d H:i:s'),
            ]);
        } else {
            // ✅ Insert sans les champs image
            $sql = "INSERT INTO {$this->tableName} 
                        (name, category, serial_number, daily_rate, available, last_maintenance) 
                    VALUES 
                        (:name, :category, :serial_number, :daily_rate, :available, :last_maintenance)";

            $this->db->execute($sql, [
                'name' => $equipment->getName(),
                'category' => $categorySlug,
                'serial_number' => $equipment->getSerialNumber(),
                'daily_rate' => $equipment->getDailyRate(),
                'available' => $equipment->isAvailable() ? 1 : 0,
                'last_maintenance' => $equipment->getLastMaintenance()?->format('Y-m-d H:i:s'),
            ]);

            $lastId = (int) $this->db->lastInsertId();
            if ($lastId) {
                $reflection = new \ReflectionClass($equipment);
                if ($reflection->hasProperty('id')) {
                    $property = $reflection->getProperty('id');
                    $property->setValue($equipment, $lastId);
                }
            }
        }
    }

    /**
     * ✅ Surcharge de hydrate pour gérer la catégorie
     */
    protected function hydrate(array $data): object
    {
        // Créer l'entité sans constructeur
        $reflection = new \ReflectionClass($this->entityClass);
        $entity = $reflection->newInstanceWithoutConstructor();

        foreach ($data as $field => $value) {
            $propertyName = $this->mapFieldToProperty($field);
            if (!$propertyName || !$reflection->hasProperty($propertyName)) {
                continue;
            }

            $property = $reflection->getProperty($propertyName);
            $type = $property->getType();

            if ($value === null) {
                $property->setValue($entity, null);
                continue;
            }

            // ✅ Gérer spécifiquement la catégorie
            if ($propertyName === 'category') {
                // Récupérer l'objet Category à partir du slug
                $category = $this->categoryRepository->findBySlug($value);
                if ($category) {
                    $property->setValue($entity, $category);
                }
                continue;
            }

            // ✅ Gérer les autres types
            if ($type && !$type->isBuiltin()) {
                $typeName = $type->getName();

                if (enum_exists($typeName)) {
                    $value = $typeName::tryFrom($value);
                } elseif ($typeName === \DateTimeImmutable::class) {
                    $value = new \DateTimeImmutable($value);
                }
            }

            $property->setValue($entity, $value);
        }

        return $entity;
    }



}