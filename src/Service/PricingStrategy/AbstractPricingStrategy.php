<?php
namespace App\Service\PricingStrategy;

use App\Entity\Equipment;
use App\Service\PricingStrategy\Calculator\PriceBreakdown;

abstract class AbstractPricingStrategy implements PricingStrategyInterface
{
    protected float $baseMultiplier = 1.0;
    protected array $applicableCategories = [];
    protected array $blacklistedCategories = [];
    protected ?int $minDays = null;
    protected ?int $maxDays = null;
    protected array $promotions = [];

    public function addPromotion(PromotionInterface $promotion): self
    {
        $this->promotions[] = $promotion;
        return $this;
    }

    public function calculateWithBreakdown(Equipment $equipment, int $days): PriceBreakdown
    {
        $breakdown = new PriceBreakdown();

        // Prix de base
        $basePrice = $equipment->getDailyRate() * $days;
        $breakdown->setBasePrice($basePrice);
        $breakdown->setDailyRate($equipment->getDailyRate());
        $breakdown->setDays($days);

        // Application du multiplicateur de la stratégie
        $multiplier = $this->getMultiplier();
        $breakdown->addAdjustment('strategie_multiplicateur', $multiplier, 'Multiplicateur ' . $this->getLabel());

        // Prix après stratégie
        $priceAfterStrategy = $basePrice * $multiplier;
        $breakdown->setPriceAfterStrategy($priceAfterStrategy);

        // Application des promotions
        $finalPrice = $priceAfterStrategy;
        foreach ($this->promotions as $promotion) {
            if ($promotion->isApplicable($equipment, $days)) {
                $discount = $promotion->calculateDiscount($equipment, $days, $finalPrice);
                if ($discount > 0) {
                    $finalPrice -= $discount;
                    $breakdown->addPromotion(
                        $promotion->getLabel(),
                        $promotion->getDescription(),
                        $discount
                    );
                }
            }
        }

        // Arrondi à 2 décimales
        $finalPrice = round($finalPrice, 2);
        $breakdown->setFinalPrice($finalPrice);
        $breakdown->setStrategy($this->getType());

        return $breakdown;
    }
    public function isApplicable(Equipment $equipment, int $days, ?array $context = []): bool
    {
        // Vérifier les jours minimum/maximum
        if ($this->minDays !== null && $days < $this->minDays) {
            return false;
        }
        if ($this->maxDays !== null && $days > $this->maxDays) {
            return false;
        }

        // Vérifier les catégories applicables
        if (!empty($this->applicableCategories)) {
            if (!in_array($equipment->getCategory()->value, $this->applicableCategories)) {
                return false;
            }
        }

        // Vérifier les catégories blacklistées
        if (!empty($this->blacklistedCategories)) {
            if (in_array($equipment->getCategory()->value, $this->blacklistedCategories)) {
                return false;
            }
        }

        return true;
    }

    public function getMultiplier(): float
    {
        return $this->baseMultiplier;
    }

    public function getType(): string
    {
        return strtolower((new \ReflectionClass($this))->getShortName());
    }


    public function getPromotions(): array
    {
        return $this->promotions;
    }
}