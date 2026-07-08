<?php
namespace App\Service\PricingStrategy;

use App\Entity\Equipment;
use App\Entity\Rental;
use App\Service\PricingStrategy\Calculator\PriceBreakdown;

interface PricingStrategyInterface
{
    /**
     * Calcule le prix total pour une location
     */
    public function calculatePrice(Equipment $equipment, int $days): float;

    /**
     * Calcule le prix avec un breakdown détaillé
     */
    public function calculateWithBreakdown(Equipment $equipment, int $days): PriceBreakdown;

    /**
     * Retourne le label de la stratégie
     */
    public function getLabel(): string;

    /**
     * Retourne la description de la stratégie
     */
    public function getDescription(): string;

    /**
     * Vérifie si la stratégie est applicable
     */
    public function isApplicable(Equipment $equipment, int $days, ?array $context = []): bool;

    /**
     * Retourne le coefficient multiplicateur
     */
    public function getMultiplier(): float;

    /**
     * Retourne le type de stratégie
     */
    public function getType(): string;
}