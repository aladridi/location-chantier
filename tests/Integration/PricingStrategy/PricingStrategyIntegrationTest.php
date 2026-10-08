<?php

namespace Tests\Integration\PricingStrategy;

use App\Core\Container\Container;
use App\Entity\Category;
use App\Entity\Equipment;
use App\Service\PricingStrategy\Collection\PricingStrategyCollection;
use App\Service\PricingStrategy\PricingStrategyInterface;
use App\Service\PricingStrategy\Calculator\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PricingStrategyIntegrationTest extends TestCase
{
    private function createContainer(): Container
    {
        $container = new Container();

        $configureServices = require __DIR__ . '/../../../config/services.php';
        $configureServices($container);

        return $container;
    }

    private function createEquipment(): Equipment
    {
        $category = new Category(
            'Excavatrice',
            'excavator'
        );

        return new Equipment(
            'Excavatrice CAT',
            $category,
            100.0
        );
    }

    public function test_container_discovers_all_pricing_strategies(): void
    {
        $container = $this->createContainer();

        $collection = $container->get(
            PricingStrategyCollection::class
        );

        $strategies = $collection->getAll();

        $this->assertCount(6, $strategies);

        foreach ($strategies as $strategy) {
            $this->assertInstanceOf(
                PricingStrategyInterface::class,
                $strategy
            );
        }
    }

    public function test_container_autowires_pricing_strategies(): void
    {
        $container = $this->createContainer();

        $collection = $container->get(
            PricingStrategyCollection::class
        );

        $strategies = $collection->getAll();

        $types = array_map(
            fn (PricingStrategyInterface $strategy): string =>
            $strategy->getType(),
            $strategies
        );

        $this->assertContains('daily', $types);
        $this->assertContains('weekly', $types);
        $this->assertContains('monthly', $types);
        $this->assertContains('seasonal', $types);
        $this->assertContains('volume', $types);
        $this->assertContains('premium', $types);
    }

    public function test_price_calculator_is_resolved_by_container(): void
    {
        $container = $this->createContainer();

        $calculator = $container->get(
            PriceCalculator::class
        );

        $this->assertInstanceOf(
            PriceCalculator::class,
            $calculator
        );
    }

    public function test_price_calculator_uses_discovered_strategies(): void
    {
        $container = $this->createContainer();

        $calculator = $container->get(
            PriceCalculator::class
        );

        $equipment = $this->createEquipment();

        $breakdown = $calculator->calculate(
            $equipment,
            7,
            'weekly'
        );

        $this->assertSame(
            'weekly',
            $breakdown->getStrategy()
        );

        $this->assertSame(
            595.0,
            $breakdown->getPriceAfterStrategy()
        );

        $this->assertSame(
            535.5,
            $breakdown->getFinalPrice()
        );
    }

    public function test_promotions_are_applied_through_complete_flow(): void
    {
        $container = $this->createContainer();

        $calculator = $container->get(
            PriceCalculator::class
        );

        $equipment = $this->createEquipment();

        $breakdown = $calculator->calculate(
            $equipment,
            7,
            'weekly'
        );

        $promotions = $breakdown->getPromotions();

        $this->assertCount(1, $promotions);

        $this->assertSame(
            'Promotion week-end',
            $promotions[0]['name']
        );

        $this->assertSame(
            59.5,
            $promotions[0]['discount']
        );

        $this->assertSame(
            59.5,
            $breakdown->getTotalDiscount()
        );
    }
}
