<?php
namespace Tests\Unit\Repository;

use App\Entity\Rental;
use App\Entity\Enum\RentalStatus;
use App\Repository\RentalRepository;
use App\Core\Database\DatabaseInterface;
use PHPUnit\Framework\TestCase;

class RentalRepositoryTest extends TestCase
{
    private RentalRepository $repository;
    private $dbMock;

    protected function setUp(): void
    {
        $this->dbMock = $this->createMock(DatabaseInterface::class);
        $this->repository = new RentalRepository($this->dbMock);
    }


    private function relationQueryResult(string $sql, array $params = []): array
    {
        if (str_contains($sql, 'FROM clients WHERE id = :id')) {
            return [[
                'id' => 1,
                'first_name' => 'Jean',
                'last_name' => 'Dupont',
                'email' => 'jean@example.com',
                'phone' => null,
                'company' => null,
                'address' => null,
                'city' => null,
                'postal_code' => null,
                'created_at' => '2024-12-01 10:00:00',
                'updated_at' => '2024-12-01 10:00:00',
            ]];
        }

        if (str_contains($sql, 'FROM equipment WHERE id = :id')) {
            return [[
                'id' => $params['id'] ?? 1,
                'name' => 'Perceuse',
                'category_id' => 1,
                'daily_rate' => 150.00,
                'available' => 1,
                'last_maintenance' => '2025-01-01 10:00:00',
                'serial_number' => 'EQ-001',
                'created_at' => '2024-12-01 10:00:00',
            ]];
        }

        if (str_contains($sql, 'FROM categories WHERE id = :id')) {
            return [[
                'id' => 1,
                'name' => 'Outillage',
                'slug' => 'outillage',
                'description' => null,
                'icon' => null,
                'color' => null,
                'daily_rate_multiplier' => 1.0,
                'requires_maintenance' => 0,
                'is_active' => 1,
                'display_order' => 0,
                'created_at' => '2024-12-01 10:00:00',
                'updated_at' => '2024-12-01 10:00:00',
            ]];
        }

        return [];
    }

    public function testFindActive(): void
    {

        $this->dbMock
            ->expects($this->atLeastOnce())
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params = []): array {
                if (str_contains($sql, 'FROM rentals')) {
                    return [$this->rentalRow(1, 1, 'active')];
                }

                return $this->relationQueryResult($sql, $params);
            });

        $results = $this->repository->findActive();

        $this->assertCount(1, $results);
        $this->assertInstanceOf(Rental::class, $results[0]);
        $this->assertEquals(RentalStatus::ACTIVE, $results[0]->getStatus());
    }


    public function testFindOverdue(): void
    {
        $this->dbMock
            ->expects($this->atLeastOnce())
            ->method('query')
            ->willReturnCallback(function (string $sql, array $params = []): array {
                if (str_contains($sql, 'FROM rentals')) {
                    return [$this->rentalRow(2, 2, 'overdue')];
                }

                if (str_contains($sql, 'FROM equipment WHERE id = :id')) {
                    $row = $this->relationQueryResult($sql, $params);

                    if (!empty($row)) {
                        $row[0]['id'] = (int) $params['id'];
                    }

                    return $row;
                }

                return $this->relationQueryResult($sql, $params);
            });

        $results = $this->repository->findOverdue();

        $this->assertCount(1, $results);
        $this->assertInstanceOf(Rental::class, $results[0]);
        $this->assertSame(RentalStatus::OVERDUE, $results[0]->getStatus());
    }

    public function testHasAvailabilityConflict(): void
    {
        $start = new \DateTimeImmutable('2025-01-01 10:00:00');
        $end = new \DateTimeImmutable('2025-01-05 10:00:00');

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('COUNT(*) as count'),
                $this->callback(function ($params) {
                    return $params['equipment_id'] === 1
                        && $params['start'] === '2025-01-01 10:00:00'
                        && $params['end'] === '2025-01-05 10:00:00';
                })
            )
            ->willReturn([['count' => 1]]);

        $conflict = $this->repository->hasAvailabilityConflict(1, $start, $end);

        $this->assertTrue($conflict);
    }

    public function testUpdateOverdueStatus(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains("SET status = 'overdue'"),
                $this->equalTo([])
            )
            ->willReturn(3);

        $updated = $this->repository->updateOverdueStatus();

        $this->assertEquals(3, $updated);
    }


    private function rentalRow(int $id, int $equipmentId, string $status): array
    {
        return [
            'id' => $id,
            'client_id' => 1,
            'equipment_id' => $equipmentId,
            'start_date' => '2025-01-01 10:00:00',
            'end_date' => '2025-01-05 10:00:00',
            'total_price' => 750.00,
            'status' => $status,
            'penalty_amount' => 0,
            'returned_at' => null,
            'notes' => null,
            'created_at' => '2024-12-01 10:00:00',
            'updated_at' => '2024-12-01 10:00:00',
        ];
    }

    private function configureRelationQueries(): void
    {
        $clientRow = [
            'id' => 1,
            'first_name' => 'Jean',
            'last_name' => 'Dupont',
            'email' => 'jean@example.com',
            'phone' => null,
            'company' => null,
            'address' => null,
            'city' => null,
            'postal_code' => null,
            'created_at' => '2024-12-01 10:00:00',
            'updated_at' => '2024-12-01 10:00:00',
        ];

        $equipmentRow = [
            'id' => 1,
            'name' => 'Perceuse',
            'category_id' => 1,
            'daily_rate' => 150.00,
            'available' => 1,
            'last_maintenance' => '2025-01-01 10:00:00',
            'serial_number' => 'EQ-001',
            'created_at' => '2024-12-01 10:00:00',
        ];

        $categoryRow = [
            'id' => 1,
            'name' => 'Outillage',
            'slug' => 'outillage',
            'description' => null,
            'icon' => null,
            'color' => null,
            'daily_rate_multiplier' => 1.0,
            'requires_maintenance' => 0,
            'is_active' => 1,
            'display_order' => 0,
            'created_at' => '2024-12-01 10:00:00',
            'updated_at' => '2024-12-01 10:00:00',
        ];

        $this->dbMock
            ->method('query')
            ->willReturnCallback(
                static function (string $sql, array $params = []) use (
                    $clientRow,
                    $equipmentRow,
                    $categoryRow
                ): array {
                    if (str_contains($sql, 'FROM clients WHERE id = :id')) {
                        return $params['id'] == 1 ? [$clientRow] : [];
                    }

                    if (str_contains($sql, 'FROM equipment WHERE id = :id')) {
                        return $params['id'] == 1 ? [$equipmentRow] : [];
                    }

                    if (str_contains($sql, 'FROM categories WHERE id = :id')) {
                        return $params['id'] == 1 ? [$categoryRow] : [];
                    }

                    if (str_contains($sql, 'FROM rentals')) {
                        return [];
                    }

                    return [];
                }
            );
    }


}