<?php
namespace App\Service;

use App\Entity\Equipment;
use App\Entity\Rental;
use App\Entity\Client;
use App\Repository\EquipmentRepositoryInterface;
use App\Repository\RentalRepositoryInterface;
use App\Service\PricingStrategy\Calculator\PriceCalculator;
use App\Core\EventDispatcher\EventDispatcherInterface;

class RentalService
{
    public function __construct(
        private EquipmentRepositoryInterface $equipmentRepo,
        private RentalRepositoryInterface $rentalRepo,
        private PriceCalculator $priceCalculator,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function rent(Client $client, int $equipmentId, int $days, ?string $strategyType = null): Rental
    {
        $equipment = $this->equipmentRepo->find($equipmentId);

        if (!$equipment || !$equipment->isAvailable()) {
            throw new \Exception('Matériel indisponible');
        }

        // Calcul du prix avec la stratégie choisie
        $breakdown = $this->priceCalculator->calculate($equipment, $days, $strategyType);
        $totalPrice = $breakdown->getFinalPrice();

        $rental = new Rental(
            $client,
            $equipment,
            new \DateTimeImmutable(),
            (new \DateTimeImmutable())->modify("+{$days} days"),
            $totalPrice
        );

        // Ajouter les détails du calcul
        $rental->setPricingBreakdown($breakdown->toArray());

        // Marquer comme loué
        $equipment->setAvailable(false);
        $this->equipmentRepo->save($equipment);
        $this->rentalRepo->save($rental);

        // Notifier
        $this->eventDispatcher->dispatch('rental.created', [
            'rental' => $rental,
            'breakdown' => $breakdown,
        ]);

        return $rental;
    }

    public function estimatePrice(int $equipmentId, int $days, ?string $strategyType = null): array
    {
        $equipment = $this->equipmentRepo->find($equipmentId);

        if (!$equipment) {
            throw new \Exception('Matériel non trouvé');
        }

        // Calcul du prix
        $breakdown = $this->priceCalculator->calculate($equipment, $days, $strategyType);

        // Comparaison des stratégies
        $comparison = $this->priceCalculator->compareStrategies($equipment, $days);

        return [
            'breakdown' => $breakdown->toArray(),
            'comparison' => $comparison,
            'best_price' => $breakdown->getFinalPrice(),
        ];
    }


}