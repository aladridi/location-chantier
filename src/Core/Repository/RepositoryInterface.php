<?php
namespace App\Core\Repository;

interface RepositoryInterface
{
    public function find(int $id): ?object;
    public function findAll(): array;
    public function findOneBy(array $criteria): ?object;
    public function findBy(array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): array;
    public function save(object $entity): void;
    public function delete(int $id): void;
    public function count(array $criteria = []): int;
    public function exists(int $id): bool;
}