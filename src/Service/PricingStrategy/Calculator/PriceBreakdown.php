<?php
namespace App\Service\PricingStrategy\Calculator;

class PriceBreakdown
{
    private float $basePrice = 0;
    private float $dailyRate = 0;
    private int $days = 0;
    private float $priceAfterStrategy = 0;
    private float $finalPrice = 0;
    private string $strategy = '';
    private array $adjustments = [];
    private array $promotions = [];

    public function setBasePrice(float $price): self
    {
        $this->basePrice = $price;
        return $this;
    }

    public function setDailyRate(float $rate): self
    {
        $this->dailyRate = $rate;
        return $this;
    }

    public function setDays(int $days): self
    {
        $this->days = $days;
        return $this;
    }

    public function setPriceAfterStrategy(float $price): self
    {
        $this->priceAfterStrategy = $price;
        return $this;
    }

    public function setFinalPrice(float $price): self
    {
        $this->finalPrice = $price;
        return $this;
    }

    public function setStrategy(string $strategy): self
    {
        $this->strategy = $strategy;
        return $this;
    }

    public function addAdjustment(string $name, float $value, string $description): self
    {
        $this->adjustments[] = [
            'name' => $name,
            'value' => $value,
            'description' => $description,
        ];
        return $this;
    }

    public function addPromotion(string $name, string $description, float $discount): self
    {
        $this->promotions[] = [
            'name' => $name,
            'description' => $description,
            'discount' => $discount,
        ];
        return $this;
    }

    public function getBasePrice(): float
    {
        return $this->basePrice;
    }

    public function getDailyRate(): float
    {
        return $this->dailyRate;
    }

    public function getDays(): int
    {
        return $this->days;
    }

    public function getPriceAfterStrategy(): float
    {
        return $this->priceAfterStrategy;
    }

    public function getFinalPrice(): float
    {
        return $this->finalPrice;
    }

    public function getStrategy(): string
    {
        return $this->strategy;
    }

    public function getAdjustments(): array
    {
        return $this->adjustments;
    }

    public function getPromotions(): array
    {
        return $this->promotions;
    }

    public function getTotalDiscount(): float
    {
        $discount = 0;
        foreach ($this->promotions as $promotion) {
            $discount += $promotion['discount'];
        }
        return $discount;
    }

    public function getDiscountPercentage(): float
    {
        if ($this->basePrice <= 0) {
            return 0;
        }
        return ($this->getTotalDiscount() / $this->basePrice) * 100;
    }

    public function toArray(): array
    {
        return [
            'base_price' => round($this->basePrice, 2),
            'daily_rate' => round($this->dailyRate, 2),
            'days' => $this->days,
            'strategy' => $this->strategy,
            'adjustments' => $this->adjustments,
            'price_after_strategy' => round($this->priceAfterStrategy, 2),
            'promotions' => $this->promotions,
            'total_discount' => round($this->getTotalDiscount(), 2),
            'discount_percentage' => round($this->getDiscountPercentage(), 2),
            'final_price' => round($this->finalPrice, 2),
        ];
    }
}