<?php

namespace Tests\Unit\Entity;

use App\Entity\Category;
use App\Entity\Client;
use App\Entity\Equipment;
use App\Entity\Rental;
use App\Entity\Enum\RentalStatus;
use PHPUnit\Framework\TestCase;

class RentalTest extends TestCase
{
    private Client $client;
    private Equipment $equipment;

    protected function setUp(): void
    {
        $category = new Category(
            name: 'Excavatrice',
            slug: 'excavator'
        );

        $this->client = new Client(
            'Jean',
            'Dupont',
            'jean@email.com'
        );

        $this->equipment = new Equipment(
            'Pelle',
            $category,
            150.00
        );
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

        $this->assertSame(RentalStatus::PENDING, $rental->getStatus());
        $this->assertSame(5, $rental->getDurationInDays());
        $this->assertEquals(750.00, $rental->getTotalPrice());
        $this->assertFalse($rental->isOverdue());
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

        $this->assertSame(RentalStatus::ACTIVE, $rental->getStatus());
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

        $this->assertSame(RentalStatus::RETURNED, $rental->getStatus());
        $this->assertTrue($rental->isReturned());
        $this->assertNotNull($rental->getReturnedAt());
    }

    public function testRentalOverdueDetection(): void
    {
        $rental = new Rental(
            $this->client,
            $this->equipment,
            new \DateTimeImmutable('-10 days'),
            new \DateTimeImmutable('-5 days'),
            750.00,
            RentalStatus::ACTIVE
        );

        $rental->markAsOverdue();

        $this->assertTrue($rental->isOverdue());
        $this->assertSame(RentalStatus::OVERDUE, $rental->getStatus());
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