<?php
namespace App\Repository;

use App\Entity\Client;
use App\Core\Repository\Criteria\Criteria;  // ✅ Import correct

interface ClientRepositoryInterface extends \App\Core\Repository\RepositoryInterface
{
    // Méthodes spécifiques
    public function findByEmail(string $email): ?Client;
    public function searchByName(string $search, int $limit = 10): array;
    public function findActiveClients(): array;
    public function findTopClients(int $limit = 10): array;
    public function getStatistics(): array;
    public function getTopClientStatistics(int $limit = 10): array;
    public function emailExists(string $email, ?int $excludeId = null): bool;
    public function getClientRentals(int $clientId): array;
    public function getActiveRentalsCount(int $clientId): int;

    // ✅ Signature corrigée avec le bon namespace
    public function search(array $criteria, ?Criteria $pagination = null): array;
}