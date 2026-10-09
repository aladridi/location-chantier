<?php

namespace Tests\Unit\PricingStrategy;

use App\Entity\Category;
use App\Entity\Equipment;
use App\Service\PricingStrategy\DailyPricing;
use App\Service\PricingStrategy\WeeklyPricing;
use App\Service\PricingStrategy\MonthlyPricing;
use App\Service\PricingStrategy\SeasonalPricing;
use App\Service\PricingStrategy\VolumeDiscountPricing;
use App\Service\PricingStrategy\PremiumPricing;
use App\Service\PricingStrategy\Calculator\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    private Equipment $equipment;
    private PriceCalculator $calculator;

    private function createCategory(
        string $name,
        string $slug,
        float  $multiplier = 1.0,
        bool   $requiresMaintenance = false
    ): Category
    {
        return new Category(
            name: $name,
            slug: $slug,
            dailyRateMultiplier: $multiplier,
            requiresMaintenance: $requiresMaintenance
        );
    }

    protected function setUp(): void
    {
        $category = $this->createCategory(
            'Excavatrice',
            'excavator'
        );

        $this->equipment = new Equipment(
            'Pelle mecanique',
            $category,
            150.00
        );

        $strategies = [
            new DailyPricing(),
            new WeeklyPricing(),
            new MonthlyPricing(),
            new SeasonalPricing(),
            new VolumeDiscountPricing(),
            new PremiumPricing(),
        ];

        $this->calculator = new PriceCalculator($strategies);
    }

    public function testDailyPricing(): void
    {
        // On demande explicitement le tarif journalier.
        $breakdown = $this->calculator->calculate(
            $this->equipment,
            3,
            'daily'
        );

        $this->assertEquals(450.00, $breakdown->getBasePrice());
        $this->assertEquals(450.00, $breakdown->getFinalPrice());
        $this->assertEquals('daily', $breakdown->getStrategy());
    }

    public function testWeeklyPricing(): void
    {
        $breakdown = $this->calculator->calculate(
            $this->equipment,
            7,
            'weekly'
        );

        // 7 jours à 150 €, avec 15 % de réduction,
        // puis la promotion supplémentaire actuelle de 10 %.
        $expected = 150 * 7 * 0.85 * 0.90;

        $this->assertEquals($expected, $breakdown->getFinalPrice());
        $this->assertEquals('weekly', $breakdown->getStrategy());
    }

    public function testMonthlyPricing(): void
    {
        $breakdown = $this->calculator->calculate(
            $this->equipment,
            30,
            'monthly'
        );

        // Tarif mensuel avec la promotion de journée gratuite
        // et les autres promotions actuellement applicables.
        $this->assertEquals('monthly', $breakdown->getStrategy());
        $this->assertLessThan(
            150 * 30,
            $breakdown->getFinalPrice()
        );
    }


    public function testVolumeDiscountPricing(): void
    {
        $breakdown = $this->calculator->calculate(
            $this->equipment,
            7,
            'volume'
        );

        $this->assertEquals('volume', $breakdown->getStrategy());
        $this->assertEquals(892.50, $breakdown->getFinalPrice());

        $breakdown2 = $this->calculator->calculate(
            $this->equipment,
            10,
            'volume'
        );

        // Remise de 20 % à partir de 10 jours.
        $this->assertEquals(1200.00, $breakdown2->getFinalPrice());
    }


    public function testPremiumPricing(): void
    {
        $category = $this->createCategory(
            'Grue',
            'crane',
            1.5,
            true
        );

        $premiumEquipment = new Equipment(
            'Grue',
            $category,
            200.00
        );

        $breakdown = $this->calculator->calculate(
            $premiumEquipment,
            5,
            'premium'
        );

        // Supplément premium de 50 % pour une grue.
        $this->assertEquals(1500.00, $breakdown->getFinalPrice());
        $this->assertEquals('premium', $breakdown->getStrategy());
    }

    public function testStrategyComparison(): void
    {
        $comparison = $this->calculator->compareStrategies(
            $this->equipment,
            7
        );

        $this->assertNotEmpty($comparison);
        $this->assertArrayHasKey('strategy', $comparison[0]);
        $this->assertArrayHasKey('price', $comparison[0]);
        $this->assertArrayHasKey('breakdown', $comparison[0]);

        $firstPrice = $comparison[0]['price'];
        $lastPrice = $comparison[count($comparison) - 1]['price'];

        // Les stratégies sont classées de la moins chère à la plus chère.
        $this->assertLessThanOrEqual($lastPrice, $firstPrice);
    }

    public function testAutomaticBestStrategySelection(): void
    {
        // À 3 jours, la stratégie volume applique 10 % de réduction :
        // 450 € - 10 % = 405 €. Elle est moins chère que le tarif journalier.
        $price = $this->calculator->calculatePrice(
            $this->equipment,
            3
        );

        $this->assertEquals(405.00, $price);

        $price7 = $this->calculator->calculatePrice(
            $this->equipment,
            7
        );

        $dailyPrice7 = 150 * 7;

        $this->assertLessThan($dailyPrice7, $price7);
    }


    public function testSeasonalPricing(): void
    {
        $breakdown = $this->calculator->calculate(
            $this->equipment,
            5,
            'seasonal'
        );

        $this->assertEquals('seasonal', $breakdown->getStrategy());
        $this->assertEquals(675.00, $breakdown->getFinalPrice());
    }


}
