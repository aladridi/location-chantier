<?php
namespace App\Service\PricingStrategy\Calculator;

use App\Entity\Equipment;
use App\Service\PricingStrategy\PricingStrategyInterface;

class PriceCalculator
{
    private array $strategies = [];
    private ?PricingStrategyInterface $defaultStrategy = null;

    public function __construct(iterable $strategies)
    {
        foreach ($strategies as $strategy) {
            if ($strategy instanceof PricingStrategyInterface) {
                $this->strategies[] = $strategy;
            }
        }
    }

    public function calculate(Equipment $equipment, int $days, ?string $strategyType = null): PriceBreakdown
    {
        $strategy = $this->selectStrategy($equipment, $days, $strategyType);

        if (!$strategy) {
            throw new \RuntimeException('Aucune stratégie de tarification applicable');
        }

        return $strategy->calculateWithBreakdown($equipment, $days);
    }

    public function calculatePrice(Equipment $equipment, int $days, ?string $strategyType = null): float
    {
        $breakdown = $this->calculate($equipment, $days, $strategyType);
        return $breakdown->getFinalPrice();
    }

    private function selectStrategy(Equipment $equipment, int $days, ?string $strategyType = null): ?PricingStrategyInterface
    {
        // Si un type spécifique est demandé
        if ($strategyType) {
            foreach ($this->strategies as $strategy) {
                if ($strategy->getType() === $strategyType && $strategy->isApplicable($equipment, $days)) {
                    return $strategy;
                }
            }
            throw new \RuntimeException("Stratégie {$strategyType} non applicable");
        }

        // Sélection automatique de la meilleure stratégie
        $bestStrategy = null;
        $bestPrice = PHP_FLOAT_MAX;

        foreach ($this->strategies as $strategy) {
            if ($strategy->isApplicable($equipment, $days)) {
                $price = $strategy->calculatePrice($equipment, $days);
                if ($price < $bestPrice) {
                    $bestPrice = $price;
                    $bestStrategy = $strategy;
                }
            }
        }

        return $bestStrategy;
    }

    public function compareStrategies(Equipment $equipment, int $days): array
    {
        $comparison = [];

        foreach ($this->strategies as $strategy) {
            if ($strategy->isApplicable($equipment, $days)) {
                $breakdown = $strategy->calculateWithBreakdown($equipment, $days);
                $comparison[] = [
                    'strategy' => $strategy->getType(),
                    'label' => $strategy->getLabel(),
                    'description' => $strategy->getDescription(),
                    'price' => $breakdown->getFinalPrice(),
                    'breakdown' => $breakdown->toArray(),
                ];
            }
        }

        // Trier par prix
        usort($comparison, function ($a, $b) {
            return $a['price'] <=> $b['price'];
        });

        return $comparison;
    }

    public function getAvailableStrategies(Equipment $equipment, int $days): array
    {
        $available = [];
        foreach ($this->strategies as $strategy) {
            if ($strategy->isApplicable($equipment, $days)) {
                $available[] = $strategy;
            }
        }
        return $available;
    }
}