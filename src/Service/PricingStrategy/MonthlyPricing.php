<?php
namespace App\Service\PricingStrategy;

use App\Entity\Equipment;
use App\Service\PricingStrategy\Promotion\FreeDayPromotion;
use App\Service\PricingStrategy\Promotion\FixedDiscountPromotion;

class MonthlyPricing extends AbstractPricingStrategy
{
    private const MONTH_DISCOUNT = 0.25; // 25% de réduction

    public function __construct()
    {
        $this->baseMultiplier = 0.75;
        $this->minDays = 14;
        $this->maxDays = 90;

        $this->addPromotion(
            new FreeDayPromotion(
                'Offre 1 jour gratuit',
                '1 jour offert pour 5 jours de location',
                5,
                1,
                [
                    'excavator',
                    'loader'
                ]
            )
        );

        $this->addPromotion(
            new FixedDiscountPromotion(
                'Remise fidélité',
                'Remise de 50€ pour les locations de plus de 20 jours',
                50,
                minDays: 20,
                minPrice: 500
            )
        );
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

    public function getType(): string
    {
        return 'monthly';
    }

    public function getDescription(): string
    {
        return sprintf(
            'Tarif économique pour les locations longues. Réduction de %.0f%% (plus 5%% supplémentaire au-delà de 60 jours)',
            self::MONTH_DISCOUNT * 100
        );
    }
}