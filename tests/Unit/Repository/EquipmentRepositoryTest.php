<?php
namespace Tests\Unit\Repository;

use App\Entity\Equipment;
use App\Entity\Enum\EquipmentCategory;
use App\Repository\EquipmentRepository;
use App\Core\Database\DatabaseInterface;
use PHPUnit\Framework\TestCase;

class EquipmentRepositoryTest extends TestCase
{
    private EquipmentRepository $repository;
    private $dbMock;

    protected function setUp(): void
    {
        $this->dbMock = $this->createMock(DatabaseInterface::class);
        $this->repository = new EquipmentRepository($this->dbMock);
    }

    public function testFindAvailable(): void
    {
        $expectedData = [
            ['id' => 1, 'name' => 'Pelle', 'category' => 'excavator', 'daily_rate' => 150.00, 'available' => 1],
            ['id' => 2, 'name' => 'Grue', 'category' => 'crane', 'daily_rate' => 200.00, 'available' => 1],
        ];

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('available = 1'),
                $this->equalTo([])
            )
            ->willReturn($expectedData);

        $results = $this->repository->findAvailable();

        $this->assertCount(2, $results);
        $this->assertInstanceOf(Equipment::class, $results[0]);
        $this->assertEquals('Pelle', $results[0]->name);
    }

    public function testFindByCategory(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('category = :category'),
                $this->equalTo(['category' => 'crane'])
            )
            ->willReturn([
                ['id' => 2, 'name' => 'Grue', 'category' => 'crane', 'daily_rate' => 200.00, 'available' => 1],
            ]);

        $results = $this->repository->findByCategory(EquipmentCategory::CRANE);

        $this->assertCount(1, $results);
        $this->assertEquals('Grue', $results[0]->name);
        $this->assertEquals(EquipmentCategory::CRANE, $results[0]->getCategory());
    }

    public function testSaveNewEquipment(): void
    {
        $equipment = new Equipment(
            'Nouvelle Pelle',
            EquipmentCategory::EXCAVATOR,
            180.00
        );

        $this->dbMock
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO equipment'),
                $this->callback(function ($params) {
                    return $params[0] === 'Nouvelle Pelle'
                        && $params[1] === EquipmentCategory::EXCAVATOR->value;
                })
            )
            ->willReturn(1);

        $this->dbMock
            ->expects($this->once())
            ->method('lastInsertId')
            ->willReturn('5');

        $this->repository->save($equipment);

        $this->assertEquals(5, $equipment->getId());
    }

    public function testFindNeedingMaintenance(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('last_maintenance IS NULL OR last_maintenance < DATE_SUB(NOW(), INTERVAL :days DAY)'),
                $this->equalTo(['days' => 90])
            )
            ->willReturn([
                ['id' => 3, 'name' => 'Vieille Grue', 'category' => 'crane', 'daily_rate' => 150.00, 'available' => 1],
            ]);

        $results = $this->repository->findNeedingMaintenance();

        $this->assertCount(1, $results);
        $this->assertEquals('Vieille Grue', $results[0]->name);
    }

    public function testGetStatistics(): void
    {
        $expectedStats = [
            'total' => 10,
            'available' => 7,
            'rented' => 3,
            'categories' => 4,
            'avg_daily_rate' => 175.50,
        ];

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('SELECT COUNT(*) as total'))
            ->willReturn([$expectedStats]);

        $stats = $this->repository->getStatistics();

        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(7, $stats['available']);
        $this->assertEquals(3, $stats['rented']);
        $this->assertEquals(175.50, $stats['avg_daily_rate']);
    }
}