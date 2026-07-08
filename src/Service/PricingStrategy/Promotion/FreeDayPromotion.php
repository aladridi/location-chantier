<?php
namespace App\Service\PricingStrategy\Promotion;

use App\Entity\Equipment;
use App\Service\PricingStrategy\PromotionInterface;

class FreeDayPromotion implements PromotionInterface
{
    public function __construct(
        private string $label,
        private string $description,
        private int $daysRequired,
        private int $daysFree,
        private ?array $applicableCategories = null
    ) {}

    public function calculateDiscount(Equipment $equipment, int $days, float $currentPrice): float
    {
        if (!$this->isApplicable($equipment, $days)) {
            return 0;
        }

        $dailyRate = $equipment->getDailyRate();
        $freeDays = floor($days / $this->daysRequired) * $this->daysFree;

        return $dailyRate * min($freeDays, $days);
    }

    public function isApplicable(Equipment $equipment, int $days): bool
    {
        if ($days < $this->daysRequired) {
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