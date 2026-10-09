<?php

namespace Tests\Unit;

use App\Core\EventDispatcher\EventDispatcherInterface;
use App\Entity\Category;
use App\Entity\Client;
use App\Entity\Equipment;
use App\Repository\EquipmentRepository;
use App\Repository\RentalRepository;
use App\Service\PricingStrategy\Calculator\PriceCalculator;
use App\Service\PricingStrategy\DailyPricing;
use App\Service\PricingStrategy\WeeklyPricing;
use App\Service\PricingStrategy\MonthlyPricing;
use App\Service\PricingStrategy\SeasonalPricing;
use App\Service\PricingStrategy\VolumeDiscountPricing;
use App\Service\PricingStrategy\PremiumPricing;
use App\Service\RentalService;
use PHPUnit\Framework\TestCase;

class RentalServiceTest extends TestCase
{
    private RentalService $rentalService;
    private EquipmentRepository $equipmentRepo;
    private RentalRepository $rentalRepo;
    private EventDispatcherInterface $eventDispatcher;

    protected function setUp(): void
    {
        $this->equipmentRepo = $this->createMock(
            EquipmentRepository::class
        );

        $this->rentalRepo = $this->createMock(
            RentalRepository::class
        );

        $this->eventDispatcher = $this->createMock(
            EventDispatcherInterface::class
        );

        $priceCalculator = new PriceCalculator([
            new DailyPricing(),
            new WeeklyPricing(),
            new MonthlyPricing(),
            new SeasonalPricing(),
            new VolumeDiscountPricing(),
            new PremiumPricing(),
        ]);

        $this->rentalService = new RentalService(
            $this->equipmentRepo,
            $this->rentalRepo,
            $priceCalculator,
            $this->eventDispatcher
        );
    }

    private function createEquipment(bool $available = true): Equipment
    {
        $category = new Category(
            name: 'Engin',
            slug: 'excavator'
        );

        return new Equipment(
            'Pelle mecanique',
            $category,
            150.00,
            $available
        );
    }

    private function createClient(): Client
    {
        return new Client(
            'Jean',
            'Dupont',
            'jean@email.com'
        );
    }

    public function testRentEquipmentSuccess(): void
    {
        $equipment = $this->createEquipment();
        $client = $this->createClient();

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

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                'rental.created',
                $this->callback(
                    fn (array $data): bool =>
                    isset($data['rental'], $data['breakdown'])
                )
            );

        $rental = $this->rentalService->rent(
            $client,
            1,
            5,
            'daily'
        );

        $this->assertEquals(750.00, $rental->getTotalPrice());
        $this->assertFalse($equipment->isAvailable());
        $this->assertSame($client, $rental->getClient());
        $this->assertSame($equipment, $rental->getEquipment());
    }

    public function testRentUnavailableEquipmentThrowsException(): void
    {
        $equipment = $this->createEquipment(false);
        $client = $this->createClient();

        $this->equipmentRepo
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($equipment);

        $this->equipmentRepo
            ->expects($this->never())
            ->method('save');

        $this->rentalRepo
            ->expects($this->never())
            ->method('save');

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Matériel indisponible');

        $this->rentalService->rent($client, 1, 5);
    }
}
