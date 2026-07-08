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

    public function testFindActive(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains("status IN ('pending', 'active', 'overdue')"),
                $this->equalTo([])
            )
            ->willReturn([
                [
                    'id' => 1,
                    'client_id' => 1,
                    'equipment_id' => 1,
                    'start_date' => '2025-01-01 10:00:00',
                    'end_date' => '2025-01-05 10:00:00',
                    'total_price' => 750.00,
                    'status' => 'active',
                ]
            ]);

        $results = $this->repository->findActive();

        $this->assertCount(1, $results);
        $this->assertInstanceOf(Rental::class, $results[0]);
        $this->assertEquals(RentalStatus::ACTIVE, $results[0]->status);
    }

    public function testFindOverdue(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains("status = 'active' AND end_date < NOW()"),
                $this->equalTo([])
            )
            ->willReturn([
                [
                    'id' => 2,
                    'client_id' => 1,
                    'equipment_id' => 2,
                    'start_date' => '2024-12-20 10:00:00',
                    'end_date' => '2024-12-25 10:00:00',
                    'total_price' => 750.00,
                    'status' => 'active',
                ]
            ]);

        $results = $this->repository->findOverdue();

        $this->assertCount(1, $results);
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
                $this->stringContains("UPDATE rentals SET status = 'overdue'"),
                $this->equalTo([])
            )
            ->willReturn(3);

        $updated = $this->repository->updateOverdueStatus();

        $this->assertEquals(3, $updated);
    }
}