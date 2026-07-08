<?php
namespace App\Service\PricingStrategy;

use App\Entity\Equipment;

interface PromotionInterface
{
    public function calculateDiscount(Equipment $equipment, int $days, float $currentPrice): float;
    public function isApplicable(Equipment $equipment, int $days): bool;
    public function getLabel(): string;
    public function getDescription(): string;
}