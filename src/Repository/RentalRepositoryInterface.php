<?php
namespace App\Repository;

use App\Entity\Rental;

interface RentalRepositoryInterface
{
    public function find(int $id): ?object;  // ✅ Changé : ?Rental -> ?object
    public function findAll(): array;
    public function findOneBy(array $criteria): ?object;  // ✅ Changé : ?Rental -> ?object
    public function findBy(array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): array;
    public function save(Rental $rental): void;
    public function delete(int $id): void;
    public function count(array $criteria = []): int;
    public function exists(int $id): bool;

    // Méthodes spécifiques
    public function findActive(): array;
    public function findOverdue(): array;
    public function findByClient(int $clientId): array;
    public function findByEquipment(int $equipmentId): array;
    public function findByPeriod(\DateTimeImmutable $start, \DateTimeImmutable $end): array;
}