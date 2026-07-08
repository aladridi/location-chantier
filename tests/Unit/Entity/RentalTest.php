<?php
namespace Tests\Unit\Entity;

use App\Entity\Client;
use App\Entity\Equipment;
use App\Entity\Rental;
use App\Entity\Enum\EquipmentCategory;
use App\Entity\Enum\RentalStatus;
use PHPUnit\Framework\TestCase;

class RentalTest extends TestCase
{
    private Client $client;
    private Equipment $equipment;

    protected function setUp(): void
    {
        $this->client = new Client('Jean', 'Dupont', 'jean@email.com');
        $this->equipment = new Equipment('Pelle', EquipmentCategory::EXCAVATOR, 150.00);
    }

    public function testRentalCreation(): void
    {
        $startDate = new \DateTimeImmutable();
        $endDate = $startDate->modify('+5 days');

        $rental = new Rental(
            $this->client,
            $this->equipment,
            $startDate,
            $endDate,
            750.00
        );

        $this->assertEquals(RentalStatus::PENDING, $rental->status);
        $this->assertEquals(5, $rental->durationInDays);
        $this->assertEquals(750.00, $rental->totalPrice);
        $this->assertFalse($rental->isOverdue);
        $this->assertTrue($rental->isActive());
    }

    public function testRentalConfirmation(): void
    {
        $rental = new Rental(
            $this->client,
            $this->equipment,
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+5 days'),
            750.00
        );

        $rental->confirm();

        $this->assertEquals(RentalStatus::ACTIVE, $rental->status);
        $this->assertFalse($this->equipment->isAvailable());
    }

    public function testRentalReturn(): void
    {
        $rental = new Rental(
            $this->client,
            $this->equipment,
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+5 days'),
            750.00,
            RentalStatus::ACTIVE
        );

        $rental->return();

        $this->assertEquals(RentalStatus::RETURNED, $rental->status);
        $this->assertTrue($this->equipment->isAvailable());
    }

    public function testRentalOverdueDetection(): void
    {
        $startDate = new \DateTimeImmutable('-10 days');
        $endDate = new \DateTimeImmutable('-5 days');

        $rental = new Rental(
            $this->client,
            $this->equipment,
            $startDate,
            $endDate,
            750.00,
            RentalStatus::ACTIVE
        );

        $this->assertTrue($rental->isOverdue);
        $this->assertGreaterThan(0, $rental->overdueDays);
        $this->assertGreaterThan(0, $rental->penaltyAmount);
    }

    public function testInvalidStatusTransition(): void
    {
        $rental = new Rental(
            $this->client,
            $this->equipment,
            new \DateTimeImmutable(),
            new \DateTimeImmutable('+5 days'),
            750.00,
            RentalStatus::RETURNED
        );

        $this->expectException(\RuntimeException::class);
        $rental->confirm();
    }
}