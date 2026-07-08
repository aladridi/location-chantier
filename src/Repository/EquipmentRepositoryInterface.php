<?php
namespace App\Repository;

use App\Entity\Equipment;

interface EquipmentRepositoryInterface
{
    public function find(int $id): ?object;  // ✅ Changé : ?Equipment -> ?object
    public function findAll(): array;
    public function findOneBy(array $criteria): ?object;  // ✅ Changé : ?Equipment -> ?object
    public function findBy(array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): array;
    public function save(Equipment $equipment): void;
    public function delete(int $id): void;
    public function count(array $criteria = []): int;
    public function exists(int $id): bool;
}