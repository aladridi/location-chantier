<?php
namespace App\Service\PricingStrategy\Factory;

use App\Service\PricingStrategy\{
    DailyPricing,
    WeeklyPricing,
    MonthlyPricing,
    SeasonalPricing,
    VolumeDiscountPricing,
    PremiumPricing,
    PricingStrategyInterface
};
use App\Service\PricingStrategy\Promotion\{
    PercentagePromotion,
    FixedDiscountPromotion,
    FreeDayPromotion
};
use App\Entity\Enum\EquipmentCategory;

class PricingStrategyFactory
{
    public static function createDaily(): DailyPricing
    {
        return new DailyPricing();
    }

    public static function createWeekly(): WeeklyPricing
    {
        return new WeeklyPricing();
    }

    public static function createMonthly(): MonthlyPricing
    {
        return new MonthlyPricing();
    }

    public static function createSeasonal(): SeasonalPricing
    {
        return new SeasonalPricing();
    }

    public static function createVolumeDiscount(): VolumeDiscountPricing
    {
        return new VolumeDiscountPricing();
    }

    public static function createPremium(): PremiumPricing
    {
        return new PremiumPricing();
    }

    /**
     * Crée une stratégie avec des promotions selon le type
     * ✅ CORRIGÉ : Ajout de tous les types de stratégies
     */
    public static function createWithPromotions(string $type): PricingStrategyInterface
    {
        // Normaliser le type (enlever 'pricing' à la fin si présent)
        $normalizedType = str_replace('pricing', '', strtolower($type));

        $strategy = match($normalizedType) {
            'daily' => new DailyPricing(),
            'weekly' => new WeeklyPricing(),
            'monthly' => new MonthlyPricing(),
            'seasonal' => new SeasonalPricing(),
            'volumediscount' => new VolumeDiscountPricing(), // ✅ AJOUTÉ
            'volumediscountpricing' => new VolumeDiscountPricing(), // ✅ AJOUTÉ (au cas où)
            'volume' => new VolumeDiscountPricing(), // ✅ AJOUTÉ (alias)
            'premium' => new PremiumPricing(),
            default => throw new \InvalidArgumentException("Unknown strategy type: {$type}"),
        };

        // Ajouter des promotions selon le type normalisé
        switch ($normalizedType) {
            case 'weekly':
                $strategy->addPromotion(
                    new PercentagePromotion(
                        'Promotion week-end',
                        'Réduction de 10% pour les locations incluant le week-end',
                        10,
                        minDays: 3
                    )
                );
                break;

            case 'monthly':
                $strategy->addPromotion(
                    new FreeDayPromotion(
                        'Offre 1 jour gratuit',
                        '1 jour offert pour 5 jours de location',
                        5,
                        1,
                        [EquipmentCategory::EXCAVATOR->value, EquipmentCategory::LOADER->value]
                    )
                );
                $strategy->addPromotion(
                    new FixedDiscountPromotion(
                        'Remise fidélité',
                        'Remise de 50€ pour les locations de plus de 20 jours',
                        50,
                        minDays: 20,
                        minPrice: 500
                    )
                );
                break;

            case 'seasonal':
                $strategy->addPromotion(
                    new PercentagePromotion(
                        'Promotion basse saison',
                        'Réduction supplémentaire de 10% en basse saison',
                        10
                    )
                );
                break;

            case 'volumediscount':
            case 'volume':
                $strategy->addPromotion(
                    new PercentagePromotion(
                        'Super remise volume',
                        '5% de réduction supplémentaire pour les très longues durées',
                        5,
                        minDays: 14
                    )
                );
                break;

            case 'premium':
                // Pas de promotions pour le premium
                break;
        }

        return $strategy;
    }

    public static function createAllStrategies(): array
    {
        return [
            self::createDaily(),
            self::createWeekly(),
            self::createMonthly(),
            self::createSeasonal(),
            self::createVolumeDiscount(),
            self::createPremium(),
        ];
    }
}