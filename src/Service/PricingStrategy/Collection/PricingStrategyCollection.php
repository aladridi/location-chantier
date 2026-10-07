<?php

namespace App\Service\PricingStrategy\Collection;

use App\Core\Container\Container;
use App\Entity\Equipment;
use App\Service\PricingStrategy\PricingStrategyInterface;

class PricingStrategyCollection
{
    /**
     * @param array<class-string<PricingStrategyInterface>> $strategyClasses
     */
    public function __construct(
        private Container $container,
        private array $strategyClasses
    ) {
    }

    /**
     * @return PricingStrategyInterface[]
     */
    public function getAll(): array
    {
        return array_map(
            fn (string $class): PricingStrategyInterface =>
            $this->container->get($class),
            $this->strategyClasses
        );
    }

    public function count(): int
    {
        return count($this->strategyClasses);
    }

    public function find(string $type): ?PricingStrategyInterface
    {
        foreach ($this->getAll() as $strategy) {
            if ($strategy->getType() === $type) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * @return PricingStrategyInterface[]
     */
    public function getApplicableStrategies(
        Equipment $equipment,
        int $days
    ): array {
        return array_values(
            array_filter(
                $this->getAll(),
                fn (PricingStrategyInterface $strategy): bool =>
                $strategy->isApplicable($equipment, $days)
            )
        );
    }

    public function getBestPrice(
        Equipment $equipment,
        int $days
    ): ?PricingStrategyInterface {
        $bestStrategy = null;
        $bestPrice = PHP_FLOAT_MAX;

        foreach ($this->getApplicableStrategies($equipment, $days) as $strategy) {
            $price = $strategy->calculatePrice($equipment, $days);

            if ($price < $bestPrice) {
                $bestPrice = $price;
                $bestStrategy = $strategy;
            }
        }

        return $bestStrategy;
    }
}