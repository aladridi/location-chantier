<?php

namespace App\Repository;

use App\Entity\Category;
use App\Core\Repository\AbstractRepository;
use App\Core\Repository\Criteria\Criteria;

class CategoryRepository extends AbstractRepository
{
    protected function initialize(): void
    {
        $this->tableName = 'categories';
        $this->entityClass = Category::class;
    }


    /**
     * Trouve une catégorie par slug
     */
    public function findBySlug(string $slug): ?Category
    {
        $sql = "
            SELECT *
            FROM {$this->tableName}
            WHERE slug = :slug
        ";

        $result = $this->db->query($sql, [
            'slug' => $slug
        ]);

        if (empty($result)) {
            return null;
        }

        return $this->hydrate($result[0]);
    }


    /**
     * Catégories actives
     */
    public function findActive(): array
    {
        $sql = "
            SELECT *
            FROM {$this->tableName}
            WHERE is_active = 1
            ORDER BY display_order ASC, name ASC
        ";

        $results = $this->db->query($sql);

        return $this->hydrateMultiple($results);
    }


    /**
     * Catégories actives avec nombre d'équipements disponibles
     */
    public function findActiveWithEquipment(): array
    {
        $sql = "
            SELECT 
                c.*,
                COUNT(e.id) AS equipment_count
            FROM {$this->tableName} c
            LEFT JOIN equipment e 
                ON e.category_id = c.id
                AND e.available = 1
            WHERE c.is_active = 1
            GROUP BY c.id
            ORDER BY c.display_order ASC, c.name ASC
        ";

        return $this->db->query($sql);
    }


    /**
     * Toutes les catégories avec nombre d'équipements
     */
    public function findWithEquipmentCount(): array
    {
        $sql = "
            SELECT 
                c.*,
                COUNT(e.id) AS equipment_count
            FROM {$this->tableName} c
            LEFT JOIN equipment e 
                ON e.category_id = c.id
            GROUP BY c.id
            ORDER BY c.display_order ASC, c.name ASC
        ";

        return $this->db->query($sql);
    }


    /**
     * Recherche catégories
     */
    public function search(array $criteria, ?Criteria $pagination = null): array
    {
        $sql = "
            SELECT *
            FROM {$this->tableName}
        ";

        $params = [];
        $conditions = [];


        if (!empty($criteria['search'])) {

            $conditions[] = "
                (
                    name LIKE ?
                    OR slug LIKE ?
                    OR description LIKE ?
                )
            ";

            $search = "%{$criteria['search']}%";

            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }


        if (isset($criteria['is_active'])) {

            $conditions[] = "is_active = ?";

            $params[] = $criteria['is_active'] ? 1 : 0;
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

            } else {

                $sql .= "
                    ORDER BY display_order ASC, name ASC
                ";

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