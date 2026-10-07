<?php

namespace App\Service\PricingStrategy;

use App\Entity\Equipment;
use App\Service\PricingStrategy\Promotion\PercentagePromotion;

class WeeklyPricing extends AbstractPricingStrategy
{
    private const WEEK_DISCOUNT = 0.15; // 15% de réduction

    public function __construct()
    {
        $this->baseMultiplier = 0.85;
        $this->minDays = 7;
        $this->maxDays = 13;

        $this->addPromotion(
            new PercentagePromotion(
                'Promotion week-end',
                'Réduction de 10% pour les locations incluant le week-end',
                10,
                minDays: 3
            )
        );
    }

    public function calculatePrice(Equipment $equipment, int $days): float
    {
        $weeks = ceil($days / 7);
        $weeklyRate = $equipment->getDailyRate() * 7 * (1 - self::WEEK_DISCOUNT);
        return $weeklyRate * $weeks;
    }

    public function getLabel(): string
    {
        return 'Tarif hebdomadaire';
    }

    public function getType(): string
    {
        return 'weekly';
    }

    public function getDescription(): string
    {
        return sprintf(
            'Tarif préférentiel pour les locations d\'une semaine ou plus. Réduction de %.0f%%',
            self::WEEK_DISCOUNT * 100
        );
    }
}