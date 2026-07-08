<?php
namespace Tests\Unit\PricingStrategy;

use App\Entity\Equipment;
use App\Entity\Enum\EquipmentCategory;
use App\Service\PricingStrategy\{
    DailyPricing,
    WeeklyPricing,
    MonthlyPricing,
    SeasonalPricing,
    VolumeDiscountPricing,
    PremiumPricing
};
use App\Service\PricingStrategy\Calculator\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    private Equipment $equipment;
    private PriceCalculator $calculator;

    protected function setUp(): void
    {
        $this->equipment = new Equipment(
            'Pelle mécanique',
            EquipmentCategory::EXCAVATOR,
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
        $breakdown = $this->calculator->calculate($this->equipment, 3);

        $this->assertEquals(450.00, $breakdown->getBasePrice());
        $this->assertEquals(450.00, $breakdown->getFinalPrice());
        $this->assertEquals('DailyPricing', $breakdown->getStrategy());
    }

    public function testWeeklyPricing(): void
    {
        // 7 jours = 1 semaine avec 15% de réduction
        $breakdown = $this->calculator->calculate($this->equipment, 7, 'weeklypricing');

        $expected = 150 * 7 * 0.85; // 892.50
        $this->assertEquals($expected, $breakdown->getFinalPrice());
        $this->assertEquals('WeeklyPricing', $breakdown->getStrategy());
        $this->assertEquals(15, $breakdown->getDiscountPercentage());
    }

    public function testMonthlyPricing(): void
    {
        // 30 jours = 1 mois avec 25% de réduction
        $breakdown = $this->calculator->calculate($this->equipment, 30, 'monthlypricing');

        $expected = 150 * 30 * 0.75;
        $this->assertEquals($expected, $breakdown->getFinalPrice());
        $this->assertEquals('MonthlyPricing', $breakdown->getStrategy());
        $this->assertEquals(25, $breakdown->getDiscountPercentage());
    }

    public function testVolumeDiscountPricing(): void
    {
        // 7 jours avec remise volume
        $breakdown = $this->calculator->calculate($this->equipment, 7, 'volumediscountpricing');

        $this->assertEquals('VolumeDiscountPricing', $breakdown->getStrategy());

        // 10 jours = 15% de réduction
        $breakdown2 = $this->calculator->calculate($this->equipment, 10, 'volumediscountpricing');
        $this->assertEquals(1500 * 0.85, $breakdown2->getFinalPrice());
    }

    public function testPremiumPricing(): void
    {
        $premiumEquipment = new Equipment(
            'Grue',
            EquipmentCategory::CRANE,
            200.00
        );

        $breakdown = $this->calculator->calculate($premiumEquipment, 5, 'premiumpricing');

        // Prix de base : 200 * 5 = 1000
        // Multiplicateur premium pour crane : 1.5
        // Final : 1500
        $this->assertEquals(1500.00, $breakdown->getFinalPrice());
        $this->assertEquals('PremiumPricing', $breakdown->getStrategy());
    }

    public function testStrategyComparison(): void
    {
        $comparison = $this->calculator->compareStrategies($this->equipment, 7);

        $this->assertNotEmpty($comparison);
        $this->assertArrayHasKey('strategy', $comparison[0]);
        $this->assertArrayHasKey('price', $comparison[0]);
        $this->assertArrayHasKey('breakdown', $comparison[0]);

        // Le moins cher devrait être en premier
        $firstPrice = $comparison[0]['price'];
        $lastPrice = $comparison[count($comparison) - 1]['price'];
        $this->assertLessThanOrEqual($lastPrice, $firstPrice);
    }

    public function testAutomaticBestStrategySelection(): void
    {
        // Pour 3 jours, Daily devrait être le plus rentable
        $price = $this->calculator->calculatePrice($this->equipment, 3);
        $this->assertEquals(450.00, $price);

        // Pour 7 jours, Weekly devrait être moins cher que Daily
        $price7 = $this->calculator->calculatePrice($this->equipment, 7);
        $dailyPrice7 = 150 * 7; // 1050
        $this->assertLessThan($dailyPrice7, $price7);
    }

    public function testSeasonalPricing(): void
    {
        // Test en haute saison (juin)
        $breakdown = $this->calculator->calculate($this->equipment, 5, 'seasonalpricing');

        // En haute saison, prix * 1.3
        $this->assertEquals(150 * 5 * 1.3, $breakdown->getFinalPrice());
    }
}