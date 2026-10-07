<?php

namespace Tests\Unit\Core\Container;

use App\Core\Container\Container;
use App\Service\PricingStrategy\WeeklyPricing;
use PHPUnit\Framework\TestCase;
use App\Service\PricingStrategy\PricingStrategyInterface;


class ContainerTest extends TestCase
{
    public function test_it_autowires_weekly_pricing(): void
    {
        $container = new Container();

        $strategy = $container->get(WeeklyPricing::class);

        $this->assertInstanceOf(
            WeeklyPricing::class,
            $strategy
        );

        $this->assertSame(
            'weekly',
            $strategy->getType()
        );

        $this->assertSame(
            'Tarif hebdomadaire',
            $strategy->getLabel()
        );
    }

    public function test_it_discovers_all_pricing_strategies(): void
    {
        $container = new Container();

        $strategies = $container->getImplementations(
            PricingStrategyInterface::class,
            __DIR__ . '/../../../../src/Service/PricingStrategy',
            'App\Service\PricingStrategy'
        );

        $this->assertCount(6, $strategies);

        $this->assertContains(
            'App\Service\PricingStrategy\DailyPricing',
            $strategies
        );

        $this->assertContains(
            'App\Service\PricingStrategy\WeeklyPricing',
            $strategies
        );

        $this->assertContains(
            'App\Service\PricingStrategy\MonthlyPricing',
            $strategies
        );

        $this->assertContains(
            'App\Service\PricingStrategy\SeasonalPricing',
            $strategies
        );

        $this->assertContains(
            'App\Service\PricingStrategy\VolumeDiscountPricing',
            $strategies
        );

        $this->assertContains(
            'App\Service\PricingStrategy\PremiumPricing',
            $strategies
        );
    }
}