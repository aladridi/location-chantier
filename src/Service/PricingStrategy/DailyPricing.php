<?php
namespace App\Service\PricingStrategy;

use App\Entity\Equipment;

class DailyPricing extends AbstractPricingStrategy
{
    public function __construct()
    {
        $this->baseMultiplier = 1.0;
        $this->minDays = 1;
        $this->maxDays = 6;
    }

    public function calculatePrice(Equipment $equipment, int $days): float
    {
        return $equipment->getDailyRate() * $days;
    }

    public function getLabel(): string
    {
        return 'Tarif journalier';
    }

    public function getDescription(): string
    {
        return 'Tarif standard au jour le jour. Idéal pour les locations courtes de 1 à 6 jours.';
    }
}