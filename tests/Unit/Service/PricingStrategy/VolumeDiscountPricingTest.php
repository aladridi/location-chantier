<?php

namespace Tests\Unit\Service\PricingStrategy;

use App\Service\PricingStrategy\VolumeDiscountPricing;
use PHPUnit\Framework\TestCase;

class VolumeDiscountPricingTest extends TestCase
{
    public function test_volume_pricing_contains_long_duration_promotion(): void
    {
        $strategy = new VolumeDiscountPricing();

        $promotions = $strategy->getPromotions();

        $this->assertCount(1, $promotions);

        $this->assertSame(
            'Super remise volume',
            $promotions[0]->getLabel()
        );

        $this->assertSame(
            '5% de réduction supplémentaire pour les très longues durées',
            $promotions[0]->getDescription()
        );
    }
}