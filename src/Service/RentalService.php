<?php
namespace App\Service;

use App\Entity\Equipment;
use App\Entity\Rental;
use App\Entity\Client;
use App\Repository\EquipmentRepository;
use App\Repository\RentalRepository;
use App\Service\PricingStrategy\Calculator\PriceCalculator;
use App\Core\EventDispatcher\EventDispatcherInterface;

class RentalService
{
    public function __construct(
        private EquipmentRepository $equipmentRepo,
        private RentalRepository $rentalRepo,
        private PriceCalculator $priceCalculator,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function rent(Client $client, int $equipmentId, int $days, ?string $strategyType = null): Rental
    {
        $equipment = $this->equipmentRepo->find($equipmentId);

        if (!$equipment || !$equipment->isAvailable()) {
            throw new \Exception('Matériel indisponible');
        }

        $breakdown = $this->priceCalculator->calculate($equipment, $days, $strategyType);
        $totalPrice = $breakdown->getFinalPrice();

        $rental = new Rental(
            $client,
            $equipment,
            new \DateTimeImmutable(),
            (new \DateTimeImmutable())->modify("+{$days} days"),
            $totalPrice
        );

        $equipment->setAvailable(false);
        $this->equipmentRepo->save($equipment);
        $this->rentalRepo->save($rental);

        $this->eventDispatcher->dispatch('rental.created', [
            'rental' => $rental,
            'breakdown' => $breakdown,
        ]);

        return $rental;
    }

    public function returnEquipment(int $rentalId): void
    {
        $rental = $this->rentalRepo->find($rentalId);

        if (!$rental) {
            throw new \Exception('Location non trouvée');
        }

        $rental->return();
        $this->rentalRepo->save($rental);

        $this->eventDispatcher->dispatch('rental.returned', [
            'rental' => $rental,
        ]);
    }

    public function getAllRentals(): array
    {
        return $this->rentalRepo->findAll();
    }

    public function getRental(int $id): ?Rental
    {
        return $this->rentalRepo->find($id);
    }

    public function estimatePrice(int $equipmentId, int $days, ?string $strategyType = null): array
    {
        $equipment = $this->equipmentRepo->find($equipmentId);

        if (!$equipment) {
            throw new \Exception('Matériel non trouvé');
        }

        $breakdown = $this->priceCalculator->calculate($equipment, $days, $strategyType);
        $comparison = $this->priceCalculator->compareStrategies($equipment, $days);

        return [
            'breakdown' => $breakdown->toArray(),
            'comparison' => $comparison,
            'best_price' => $breakdown->getFinalPrice(),
        ];
    }

    public function checkOverdueRentals(): void
    {
        $overdue = $this->rentalRepo->findOverdue();

        foreach ($overdue as $rental) {
            $rental->markAsOverdue();
            $this->rentalRepo->save($rental);

            $this->eventDispatcher->dispatch('rental.overdue', [
                'rental' => $rental,
            ]);
        }
    }

    // ✅ AJOUT DE LA MÉTHODE getStatistics()
    public function getStatistics(): array
    {
        return $this->rentalRepo->getStatistics();
    }

    // ✅ AJOUT DE LA MÉTHODE getRecentRentals()
    public function getRecentRentals(int $limit = 5): array
    {
        return $this->rentalRepo->findBy([], ['created_at' => 'DESC'], $limit);
    }

    // ✅ AJOUT DE LA MÉTHODE getMonthlyRevenue()
    public function getMonthlyRevenue(int $months = 12): array
    {
        return $this->rentalRepo->getMonthlyRevenue($months);
    }
}