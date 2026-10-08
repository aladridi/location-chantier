<?php
namespace App\Service\PricingStrategy;

use App\Entity\Equipment;
use App\Service\PricingStrategy\Promotion\PercentagePromotion;
class VolumeDiscountPricing extends AbstractPricingStrategy
{
    private array $volumeTiers = [
        1 => 0.0,   // 0% de réduction pour 1 équipement
        3 => 0.10,  // 10% pour 3 équipements
        5 => 0.15,  // 15% pour 5 équipements
        10 => 0.20, // 20% pour 10 équipements
        20 => 0.25, // 25% pour 20 équipements
    ];

    public function __construct()
    {
        $this->baseMultiplier = 1.0;
        $this->minDays = 1;

        $this->addPromotion(
            new PercentagePromotion(
                'Super remise volume',
                '5% de réduction supplémentaire pour les très longues durées',
                5,
                minDays: 14
            )
        );
    }

    public function calculatePrice(Equipment $equipment, int $days): float
    {
        $basePrice = $equipment->getDailyRate() * $days;
        $discount = $this->getVolumeDiscount($days);
        return $basePrice * (1 - $discount);
    }

    public function getLabel(): string
    {
        return 'Tarif volume (remise progressive)';
    }

    public function getType(): string
    {
        return 'volume';
    }

    public function getDescription(): string
    {
        return 'Tarif avec remise progressive selon la durée de location. Plus vous louez longtemps, plus vous économisez !';
    }

    public function getMultiplier(): float
    {
        return 1 - $this->getVolumeDiscount(7); // Exemple pour 7 jours
    }

    private function getVolumeDiscount(int $days): float
    {
        $discount = 0.0;
        foreach ($this->volumeTiers as $threshold => $rate) {
            if ($days >= $threshold) {
                $discount = $rate;
            }
        }
        return $discount;
    }

    public function isApplicable(Equipment $equipment, int $days, ?array $context = []): bool
    {
        // Applicable pour les locations de plus de 3 jours
        return $days >= 3;
    }
}