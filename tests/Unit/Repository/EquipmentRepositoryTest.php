<?php
namespace Tests\Unit\Repository;

use App\Entity\Equipment;
use App\Entity\Category;
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
        $this->assertEquals('Pelle', $results[0]->getName());
    }

    public function testFindByCategory(): void
    {
        $category = new Category('Grue', 'crane');
        $category->setId(2);

        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with(
                $this->stringContains('e.category_id = :category_id'),
                $this->equalTo(['category_id' => 2])
            )
            ->willReturn([]);

        $results = $this->repository->findByCategory($category);

        $this->assertIsArray($results);
    }

    public function testSaveNewEquipment(): void
    {
        $category = new Category('Excavatrice', 'excavator');
        $category->setId(3);

        $equipment = new Equipment(
            'Nouvelle Pelle',
            $category,
            180.00
        );

        $this->dbMock
            ->expects($this->once())
            ->method('execute')
            ->with(
                $this->stringContains('INSERT INTO equipment'),
                $this->callback(function (array $params): bool {
                    return ($params['name'] ?? null) === 'Nouvelle Pelle'
                        && ($params['category_id'] ?? null) === 3
                        && ($params['daily_rate'] ?? null) === 180.00;
                })
            )
            ->willReturn(1);

        $this->repository->save($equipment);
    }

    public function testFindNeedingMaintenance(): void
    {
        $this->dbMock
            ->expects($this->once())
            ->method('query')
            ->with(
                $this->callback(
                    fn (string $sql): bool =>
                        str_contains($sql, 'last_maintenance IS NULL')
                        && str_contains($sql, 'DATE_SUB(NOW(), INTERVAL :days DAY)')
                ),
                $this->equalTo(['days' => 90])
            )
            ->willReturn([
                ['id' => 3, 'name' => 'Vieille Grue', 'category' => 'crane', 'daily_rate' => 150.00, 'available' => 1],
            ]);

        $results = $this->repository->findNeedingMaintenance();

        $this->assertCount(1, $results);
        $this->assertEquals('Vieille Grue', $results[0]->getName());
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
            ->with($this->stringContains('COUNT(*) as total'))
            ->willReturn([$expectedStats]);

        $stats = $this->repository->getStatistics();

        $this->assertEquals(10, $stats['total']);
        $this->assertEquals(7, $stats['available']);
        $this->assertEquals(3, $stats['rented']);
        $this->assertEquals(175.50, $stats['avg_daily_rate']);
    }
}