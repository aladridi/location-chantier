<?php
namespace App\Repository;

use App\Entity\Client;
use App\Core\Repository\Criteria\Criteria;  // ✅ Import correct

interface ClientRepositoryInterface
{
    // Méthodes de base
    public function find(int $id): ?object;
    public function findAll(): array;
    public function findOneBy(array $criteria): ?object;
    public function findBy(array $criteria, array $orderBy = [], ?int $limit = null, ?int $offset = null): array;
    public function save(Client $client): void;
    public function delete(int $id): void;
    public function count(array $criteria = []): int;
    public function exists(int $id): bool;

    // Méthodes spécifiques
    public function findByEmail(string $email): ?Client;
    public function searchByName(string $search, int $limit = 10): array;
    public function findActiveClients(): array;
    public function findTopClients(int $limit = 10): array;
    public function getStatistics(): array;
    public function emailExists(string $email, ?int $excludeId = null): bool;
    public function getClientRentals(int $clientId): array;
    public function getActiveRentalsCount(int $clientId): int;

    // ✅ Signature corrigée avec le bon namespace
    public function search(array $criteria, ?Criteria $pagination = null): array;
}