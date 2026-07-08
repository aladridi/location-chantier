<?php
namespace App\Service\PricingStrategy\Promotion;

use App\Entity\Equipment;
use App\Service\PricingStrategy\PromotionInterface;

class FixedDiscountPromotion implements PromotionInterface
{
    public function __construct(
        private string $label,
        private string $description,
        private float $amount,
        private ?int $minDays = null,
        private ?float $minPrice = null
    ) {}

    public function calculateDiscount(Equipment $equipment, int $days, float $currentPrice): float
    {
        if ($this->minPrice !== null && $currentPrice < $this->minPrice) {
            return 0;
        }

        return min($this->amount, $currentPrice);
    }

    public function isApplicable(Equipment $equipment, int $days): bool
    {
        if ($this->minDays !== null && $days < $this->minDays) {
            return false;
        }

        return true;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}