<?php
namespace App\Service\PricingStrategy;

use App\Entity\Equipment;

class SeasonalPricing extends AbstractPricingStrategy
{
    private array $seasonalRates = [
        'high' => 1.3,   // +30% en haute saison
        'medium' => 1.0, // Prix normal
        'low' => 0.85,   // -15% en basse saison
    ];

    public function __construct()
    {
        $this->baseMultiplier = 1.0;
    }

    public function calculatePrice(Equipment $equipment, int $days): float
    {
        $seasonMultiplier = $this->getSeasonMultiplier();
        return $equipment->getDailyRate() * $days * $seasonMultiplier;
    }

    public function getLabel(): string
    {
        return 'Tarif saisonnier';
    }

    public function getDescription(): string
    {
        $season = $this->getCurrentSeason();
        $multiplier = $this->seasonalRates[$season];
        $percentage = ($multiplier - 1) * 100;

        return sprintf(
            'Tarif ajusté selon la saison (%s). %s',
            $season,
            $percentage > 0 ? "+{$percentage}%" : "{$percentage}%"
        );
    }

    public function getMultiplier(): float
    {
        return $this->getSeasonMultiplier();
    }

    private function getSeasonMultiplier(): float
    {
        $month = (int) date('n');
        $season = $this->getCurrentSeason();
        return $this->seasonalRates[$season];
    }

    private function getCurrentSeason(): string
    {
        $month = (int) date('n');

        // Haute saison : juin à août (6-8)
        if ($month >= 6 && $month <= 8) {
            return 'high';
        }

        // Basse saison : novembre à février (11-2)
        if ($month >= 11 || $month <= 2) {
            return 'low';
        }

        return 'medium';
    }

    public function isApplicable(Equipment $equipment, int $days, ?array $context = []): bool
    {
        // Applicable à toutes les catégories
        return true;
    }
}