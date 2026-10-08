<?php

namespace Tests\Unit\Service\PricingStrategy\Promotion;

use App\Entity\Category;
use App\Entity\Equipment;
use App\Service\PricingStrategy\Promotion\PercentagePromotion;
use PHPUnit\Framework\TestCase;

class PercentagePromotionTest extends TestCase
{
    private function createEquipment(string $slug = 'excavator'): Equipment
    {
        $category = new Category(
            'Excavatrice',
            $slug
        );

        return new Equipment(
            'Excavatrice CAT',
            $category,
            100.0
        );
    }

    public function test_it_calculates_percentage_discount(): void
    {
        $promotion = new PercentagePromotion(
            'Remise 10%',
            'Réduction de 10%',
            10
        );

        $equipment = $this->createEquipment();

        $discount = $promotion->calculateDiscount(
            $equipment,
            5,
            500.0
        );

        $this->assertSame(50.0, $discount);
    }

    public function test_it_is_applicable_when_min_days_is_reached(): void
    {
        $promotion = new PercentagePromotion(
            'Remise longue durée',
            'Réduction à partir de 5 jours',
            10,
            minDays: 5
        );

        $equipment = $this->createEquipment();

        $this->assertTrue(
            $promotion->isApplicable($equipment, 5)
        );

        $this->assertTrue(
            $promotion->isApplicable($equipment, 10)
        );
    }

    public function test_it_is_not_applicable_before_min_days(): void
    {
        $promotion = new PercentagePromotion(
            'Remise longue durée',
            'Réduction à partir de 5 jours',
            10,
            minDays: 5
        );

        $equipment = $this->createEquipment();

        $this->assertFalse(
            $promotion->isApplicable($equipment, 4)
        );
    }

    public function test_it_can_be_restricted_to_categories(): void
    {
        $promotion = new PercentagePromotion(
            'Remise excavatrice',
            'Réduction pour les excavatrices',
            10,
            applicableCategories: ['excavator']
        );

        $excavator = $this->createEquipment('excavator');
        $bulldozer = $this->createEquipment('bulldozer');

        $this->assertTrue(
            $promotion->isApplicable($excavator, 5)
        );

        $this->assertFalse(
            $promotion->isApplicable($bulldozer, 5)
        );
    }

    public function test_it_is_applicable_to_all_categories_when_no_category_filter_is_defined(): void
    {
        $promotion = new PercentagePromotion(
            'Remise générale',
            'Réduction générale',
            10
        );

        $equipment = $this->createEquipment('bulldozer');

        $this->assertTrue(
            $promotion->isApplicable($equipment, 1)
        );
    }

    public function test_it_returns_label_and_description(): void
    {
        $promotion = new PercentagePromotion(
            'Remise 10%',
            'Réduction exceptionnelle de 10%',
            10
        );

        $this->assertSame(
            'Remise 10%',
            $promotion->getLabel()
        );

        $this->assertSame(
            'Réduction exceptionnelle de 10%',
            $promotion->getDescription()
        );
    }
}
