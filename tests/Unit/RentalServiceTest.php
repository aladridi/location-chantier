<?php
namespace Tests\Unit;

use App\Entity\Client;
use App\Entity\Equipment;
use App\Repository\EquipmentRepository;
use App\Repository\RentalRepository;
use App\Service\PricingStrategy\DailyPricing;
use App\Service\RentalService;
use App\Core\EventDispatcher\EventDispatcher;
use PHPUnit\Framework\TestCase;

class RentalServiceTest extends TestCase
{
    private RentalService $rentalService;
    private EquipmentRepository $equipmentRepo;
    private RentalRepository $rentalRepo;

    protected function setUp(): void
    {
        // Création des mocks
        $this->equipmentRepo = $this->createMock(EquipmentRepository::class);
        $this->rentalRepo = $this->createMock(RentalRepository::class);
        $eventDispatcher = $this->createMock(EventDispatcher::class);
        $pricing = new DailyPricing();

        $this->rentalService = new RentalService(
            $this->equipmentRepo,
            $this->rentalRepo,
            $pricing,
            $eventDispatcher
        );
    }

    public function testRentEquipmentSuccess(): void
    {
        // Arrange
        $equipment = new Equipment(1, 'Pelle mécanique', 'Engin', 150.0, true);
        $client = new Client(1, 'Dupont', 'Jean', 'jean@email.com');

        $this->equipmentRepo
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($equipment);

        $this->equipmentRepo
            ->expects($this->once())
            ->method('save');

        $this->rentalRepo
            ->expects($this->once())
            ->method('save');

        // Act
        $rental = $this->rentalService->rent($client, 1, 5);

        // Assert
        $this->assertEquals(750.0, $rental->getTotalPrice()); // 150 * 5
        $this->assertFalse($equipment->isAvailable());
    }

    public function testRentUnavailableEquipmentThrowsException(): void
    {
        // Arrange
        $equipment = new Equipment(1, 'Pelle mécanique', 'Engin', 150.0, false);
        $client = new Client(1, 'Dupont', 'Jean', 'jean@email.com');

        $this->equipmentRepo
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($equipment);

        // Assert & Act
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Matériel indisponible');

        $this->rentalService->rent($client, 1, 5);
    }
}