<?php

namespace App\Entity;

class Category
{
    private ?int $id = null;

    private \DateTimeImmutable $createdAt;

    private \DateTimeImmutable $updatedAt;

    public string $name {
        set (string $value) {
            $value = trim($value);

            if ($value === '') {
                throw new \InvalidArgumentException(
                    'Le nom de la catégorie ne peut pas être vide'
                );
            }

            if (strlen($value) < 2) {
                throw new \InvalidArgumentException(
                    'Le nom doit faire au moins 2 caractères'
                );
            }

            $this->name = $value;
        }
    }

public string $slug {
set (string $value) {
    $value = trim($value);

    if ($value === '') {
        throw new \InvalidArgumentException(
            'Le slug ne peut pas être vide'
        );
    }

    $slug = strtolower(
        trim(
            preg_replace('/[^a-zA-Z0-9]+/', '-', $value),
            '-'
        )
    );

    if ($slug === '') {
        throw new \InvalidArgumentException('Slug invalide');
    }

    $this->slug = $slug;
}
    }

    public ?string $description = null;

    public ?string $icon = null;

    public ?string $color = null;

    public float $dailyRateMultiplier {
set (float $value) {
    if ($value < 0) {
        throw new \InvalidArgumentException(
            'Le multiplicateur ne peut pas être négatif'
        );
    }

    $this->dailyRateMultiplier = $value;
}
    }

    public bool $requiresMaintenance = false;

    public bool $isActive = true;

    public int $displayOrder {
set (int $value) {
    $this->displayOrder = max(0, $value);
}
    }


    public function __construct(
    string $name,
    string $slug,
    ?string $description = null,
    ?string $icon = null,
    ?string $color = null,
    float $dailyRateMultiplier = 1.0,
    bool $requiresMaintenance = false,
    bool $isActive = true,
    int $displayOrder = 0
) {
    $this->name = $name;
    $this->slug = $slug;

    $this->description = $description;
    $this->icon = $icon;
    $this->color = $color;

    $this->dailyRateMultiplier = $dailyRateMultiplier;

    $this->requiresMaintenance = $requiresMaintenance;
    $this->isActive = $isActive;
    $this->displayOrder = $displayOrder;

    $this->createdAt = new \DateTimeImmutable();
    $this->updatedAt = new \DateTimeImmutable();
}


    // Getters

    public function getId(): ?int
{
    return $this->id;
}

    public function getName(): string
{
    return $this->name;
}

    public function getSlug(): string
{
    return $this->slug;
}

    public function getDescription(): ?string
{
    return $this->description;
}

    public function getIcon(): ?string
{
    return $this->icon;
}

    public function getColor(): ?string
{
    return $this->color;
}

    public function getDailyRateMultiplier(): float
{
    return $this->dailyRateMultiplier;
}

    public function requiresMaintenance(): bool
{
    return $this->requiresMaintenance;
}

    public function isActive(): bool
{
    return $this->isActive;
}

    public function getDisplayOrder(): int
{
    return $this->displayOrder;
}

    public function getCreatedAt(): \DateTimeImmutable
{
    return $this->createdAt;
}

    public function getUpdatedAt(): \DateTimeImmutable
{
    return $this->updatedAt;
}


    // Setters métier

    public function setId(int $id): self
{
    if ($this->id !== null) {
        throw new \RuntimeException(
            'L\'ID ne peut pas être modifié'
        );
    }

    $this->id = $id;

    return $this;
}


    public function touch(): void
{
    $this->updatedAt = new \DateTimeImmutable();
}


    public function toArray(): array
{
    return [
        'id' => $this->id,
        'name' => $this->name,
        'slug' => $this->slug,
        'description' => $this->description,
        'icon' => $this->icon,
        'color' => $this->color,
        'daily_rate_multiplier' => $this->dailyRateMultiplier,
        'requires_maintenance' => $this->requiresMaintenance,
        'is_active' => $this->isActive,
        'display_order' => $this->displayOrder,
        'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
        'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
    ];
}
}