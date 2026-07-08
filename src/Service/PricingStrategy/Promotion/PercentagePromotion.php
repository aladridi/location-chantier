<?php
namespace App\Service\PricingStrategy\Promotion;

use App\Entity\Equipment;
use App\Service\PricingStrategy\PromotionInterface;

class PercentagePromotion implements PromotionInterface
{
    public function __construct(
        private string $label,
        private string $description,
        private float $percentage,
        private ?int $minDays = null,
        private ?array $applicableCategories = null
    ) {}

    public function calculateDiscount(Equipment $equipment, int $days, float $currentPrice): float
    {
        return $currentPrice * ($this->percentage / 100);
    }

    public function isApplicable(Equipment $equipment, int $days): bool
    {
        if ($this->minDays !== null && $days < $this->minDays) {
            return false;
        }

        if ($this->applicableCategories !== null) {
            if (!in_array($equipment->getCategory()->value, $this->applicableCategories)) {
                return false;
            }
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