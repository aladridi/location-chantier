<?php
namespace App\Service\PricingStrategy;

use App\Entity\Equipment;

class MonthlyPricing extends AbstractPricingStrategy
{
    private const MONTH_DISCOUNT = 0.25; // 25% de réduction

    public function __construct()
    {
        $this->baseMultiplier = 0.75; // 25% de réduction
        $this->minDays = 14;
        $this->maxDays = 90;
    }

    public function calculatePrice(Equipment $equipment, int $days): float
    {
        $months = ceil($days / 30);
        $monthlyRate = $equipment->getDailyRate() * 30 * (1 - self::MONTH_DISCOUNT);

        // Si la location est très longue (> 60 jours), réduction supplémentaire
        if ($days > 60) {
            $monthlyRate *= 0.95; // 5% supplémentaire
        }

        return $monthlyRate * $months;
    }

    public function getLabel(): string
    {
        return 'Tarif mensuel';
    }

    public function getDescription(): string
    {
        return sprintf(
            'Tarif économique pour les locations longues. Réduction de %.0f%% (plus 5%% supplémentaire au-delà de 60 jours)',
            self::MONTH_DISCOUNT * 100
        );
    }
}