<?php

namespace Tests\Unit\Service\PricingStrategy;

use App\Core\Container\Container;
use App\Entity\Category;
use App\Entity\Equipment;
use App\Service\PricingStrategy\Calculator\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    private function createCalculator(): PriceCalculator
    {
        $container = new Container();

        $configureServices = require __DIR__ . '/../../../../config/services.php';
        $configureServices($container);

        return $container->get(PriceCalculator::class);
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

    public function test_it_calculates_weekly_price(): void
    {
        $calculator = $this->createCalculator();
        $equipment = $this->createEquipment();

        $breakdown = $calculator->calculate(
            $equipment,
            7,
            'weekly'
        );

        $this->assertSame(700.0, $breakdown->getBasePrice());
        $this->assertSame(595.0, $breakdown->getPriceAfterStrategy());
        $this->assertSame(535.5, $breakdown->getFinalPrice());
        $this->assertSame('weekly', $breakdown->getStrategy());
    }

    public function test_it_selects_the_cheapest_applicable_strategy_automatically(): void
    {
        $calculator = $this->createCalculator();
        $equipment = $this->createEquipment();

        $automatic = $calculator->calculate(
            $equipment,
            7
        );

        $this->assertSame(
            'volume',
            $automatic->getStrategy()
        );

        $this->assertSame(
            595.0,
            $automatic->getFinalPrice()
        );
    }
}