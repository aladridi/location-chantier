<?php
namespace App\Service\PricingStrategy\Collection;

use App\Service\PricingStrategy\PricingStrategyInterface;

class PricingStrategyCollection
{
    private array $strategies = [];

    public function __construct(array $strategies)
    {
        $this->strategies = $strategies;
    }

    public function getAll(): array
    {
        return $this->strategies;
    }

    public function count(): int
    {
        return count($this->strategies);
    }

    public function find(string $type): ?PricingStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->getType() === $type) {
                return $strategy;
            }
        }
        return null;
    }

    public function getBestPrice(Equipment $equipment, int $days): ?PricingStrategyInterface
    {
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

    public function getApplicableStrategies(Equipment $equipment, int $days): array
    {
        $applicable = [];
        foreach ($this->strategies as $strategy) {
            if ($strategy->isApplicable($equipment, $days)) {
                $applicable[] = $strategy;
            }
        }
        return $applicable;
    }
}