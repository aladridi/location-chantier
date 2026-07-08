<?php
namespace App\Service\PricingStrategy;

use App\Entity\Equipment;
use App\Entity\Enum\EquipmentCategory;

class PremiumPricing extends AbstractPricingStrategy
{
    private array $premiumCategories = [
        'crane',
        'bulldozer',
        'excavator',
    ];

    public function __construct()
    {
        $this->baseMultiplier = 1.0;
        $this->applicableCategories = $this->premiumCategories;
    }

    public function calculatePrice(Equipment $equipment, int $days): float
    {
        $basePrice = $equipment->getDailyRate() * $days;

        // Les équipements premium ont un supplément
        $premiumMultiplier = $this->getPremiumMultiplier($equipment);

        return $basePrice * $premiumMultiplier;
    }

    public function getLabel(): string
    {
        return 'Tarif premium (matériel spécialisé)';
    }

    public function getDescription(): string
    {
        return 'Tarif spécifique pour le matériel lourd et spécialisé nécessitant des compétences particulières.';
    }

    public function getMultiplier(): float
    {
        return 1.0;
    }

    private function getPremiumMultiplier(Equipment $equipment): float
    {
        // Supplément selon la catégorie
        return match($equipment->getCategory()) {
            EquipmentCategory::CRANE => 1.5,
            EquipmentCategory::BULLDOZER => 1.3,
            EquipmentCategory::EXCAVATOR => 1.2,
            default => 1.0,
        };
    }

    public function isApplicable(Equipment $equipment, int $days, ?array $context = []): bool
    {
        return in_array($equipment->getCategory()->value, $this->premiumCategories);
    }
}