<?php
namespace App\Core\Repository;

use App\Core\Database\DatabaseInterface;

abstract class AbstractRepository implements RepositoryInterface
{
    protected string $tableName;
    protected string $entityClass;
    protected array $fieldMapping = [];

    public function __construct(
        protected DatabaseInterface $db
    ) {
        $this->initialize();
    }

    protected function initialize(): void
    {
        // À surcharger dans les classes filles
    }

    public function find(int $id): ?object
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = :id";
        $result = $this->db->query($sql, ['id' => $id]);

        if (empty($result)) {
            return null;
        }

        return $this->hydrate($result[0]);
    }

    public function findAll(): array
    {
        $sql = "SELECT * FROM {$this->tableName} ORDER BY id DESC";
        $results = $this->db->query($sql);

        return $this->hydrateMultiple($results);
    }

    public function findOneBy(array $criteria): ?object
    {
        $results = $this->findBy($criteria, [], 1);
        return $results[0] ?? null;
    }

    public function findBy(array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): array
    {
        $sql = "SELECT * FROM {$this->tableName}";
        $params = [];
        $conditions = [];

        foreach ($criteria as $field => $value) {
            if (is_array($value)) {
                if (isset($value['operator'])) {
                    switch ($value['operator']) {
                        case 'IN':
                            $placeholders = implode(',', array_fill(0, count($value['value']), '?'));
                            $conditions[] = "{$field} IN ({$placeholders})";
                            $params = array_merge($params, $value['value']);
                            break;
                        case 'BETWEEN':
                            $conditions[] = "{$field} BETWEEN ? AND ?";
                            $params[] = $value['value'][0];
                            $params[] = $value['value'][1];
                            break;
                        case 'LIKE':
                            $conditions[] = "{$field} LIKE ?";
                            $params[] = "%{$value['value']}%";
                            break;
                        case '>':
                        case '<':
                        case '>=':
                        case '<=':
                        case '!=':
                            $conditions[] = "{$field} {$value['operator']} ?";
                            $params[] = $value['value'];
                            break;
                    }
                } else {
                    $conditions[] = "{$field} = ?";
                    $params[] = $value;
                }
            } else {
                $conditions[] = "{$field} = ?";
                $params[] = $value;
            }
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        if (!empty($orderBy)) {
            $orderClauses = [];
            foreach ($orderBy as $field => $direction) {
                $orderClauses[] = "{$field} " . strtoupper($direction);
            }
            $sql .= " ORDER BY " . implode(', ', $orderClauses);
        }

        if ($limit !== null) {
            $sql .= " LIMIT " . (int) $limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int) $offset;
            }
        }

        $results = $this->db->query($sql, $params);
        return $this->hydrateMultiple($results);
    }

    public function save(object $entity): void
    {
        if (!$this->supports($entity)) {
            throw new \InvalidArgumentException(
                sprintf('Entity must be instance of %s', $this->entityClass)
            );
        }

        $reflection = new \ReflectionClass($entity);
        $id = null;

        try {
            $idProperty = $reflection->getProperty('id');
            $id = $idProperty->getValue($entity);
        } catch (\ReflectionException $e) {
            // Pas de propriété id
        }

        if ($id) {
            $this->update($entity);
        } else {
            $this->insert($entity);
        }
    }

    public function delete(int $id): void
    {
        $sql = "DELETE FROM {$this->tableName} WHERE id = :id";
        $this->db->execute($sql, ['id' => $id]);
    }

    public function count(array $criteria = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->tableName}";
        $params = [];
        $conditions = [];

        foreach ($criteria as $field => $value) {
            $conditions[] = "{$field} = ?";
            $params[] = $value;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $result = $this->db->query($sql, $params);
        return (int) ($result[0]['total'] ?? 0);
    }

    public function exists(int $id): bool
    {
        return $this->find($id) !== null;
    }

    protected function hydrate(array $data): object
    {
        $reflection = new \ReflectionClass($this->entityClass);
        $entity = $reflection->newInstanceWithoutConstructor();

        foreach ($data as $field => $value) {
            $propertyName = $this->mapFieldToProperty($field);
            if ($propertyName && $reflection->hasProperty($propertyName)) {
                $property = $reflection->getProperty($propertyName);

                $type = $property->getType();
                if ($type && !$type->isBuiltin()) {
                    $typeName = $type->getName();
                    if (enum_exists($typeName)) {
                        $value = $typeName::tryFrom($value);
                    } elseif ($typeName === \DateTimeImmutable::class && $value) {
                        $value = new \DateTimeImmutable($value);
                    }
                }

                $property->setValue($entity, $value);
            }
        }

        return $entity;
    }

    protected function hydrateMultiple(array $data): array
    {
        return array_map([$this, 'hydrate'], $data);
    }

    protected function supports(object $entity): bool
    {
        return $entity instanceof $this->entityClass;
    }

    private function insert(object $entity): void
    {
        $data = $this->extractData($entity);
        unset($data['id']);

        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = sprintf(
            "INSERT INTO {$this->tableName} (%s) VALUES (%s)",
            implode(', ', $fields),
            implode(', ', $placeholders)
        );

        $params = array_values($data);
        $this->db->execute($sql, $params);

        $lastId = (int) $this->db->lastInsertId();
        if ($lastId) {
            $reflection = new \ReflectionClass($entity);
            if ($reflection->hasProperty('id')) {
                $property = $reflection->getProperty('id');
                $property->setValue($entity, $lastId);
            }
        }
    }

    private function update(object $entity): void
    {
        $data = $this->extractData($entity);

        $reflection = new \ReflectionClass($entity);
        $idProperty = $reflection->getProperty('id');
        $id = $idProperty->getValue($entity);

        if (!$id) {
            throw new \RuntimeException('Cannot update entity without ID');
        }

        $sets = [];
        $params = [];
        foreach ($data as $field => $value) {
            if ($field !== 'id') {
                $sets[] = "{$field} = ?";
                $params[] = $value;
            }
        }

        $params[] = $id;
        $sql = sprintf(
            "UPDATE {$this->tableName} SET %s WHERE id = ?",
            implode(', ', $sets)
        );

        $this->db->execute($sql, $params);
    }

    private function extractData(object $entity): array
    {
        $reflection = new \ReflectionClass($entity);
        $properties = $reflection->getProperties();
        $data = [];

        foreach ($properties as $property) {
            $fieldName = $this->mapPropertyToField($property->getName());
            if ($fieldName) {
                $value = $property->getValue($entity);

                if ($value instanceof \DateTimeImmutable) {
                    $value = $value->format('Y-m-d H:i:s');
                } elseif ($value instanceof \UnitEnum) {
                    $value = $value->value;
                } elseif (is_bool($value)) {
                    $value = $value ? 1 : 0;
                }

                $data[$fieldName] = $value;
            }
        }

        return $data;
    }

    protected function mapFieldToProperty(string $field): string
    {
        $parts = explode('_', $field);
        $property = array_shift($parts);
        foreach ($parts as $part) {
            $property .= ucfirst($part);
        }
        return $property;
    }

    protected function mapPropertyToField(string $property): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $property));
    }
}