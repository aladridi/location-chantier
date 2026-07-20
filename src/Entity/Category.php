<?php

namespace App\Entity;

use App\Attribute\Entity;
use App\Attribute\Table;
use App\Attribute\Column;
use App\Attribute\Id;

#[Entity]
#[Table('categories')]
class Category
{
    #[Id]
    #[Column('id')]
    private ?int $id = null;

    #[Column('name')]
    private string $name;

    #[Column('slug')]
    private string $slug;

    #[Column('description')]
    private ?string $description = null;

    #[Column('icon')]
    private ?string $icon = null;

    #[Column('color')]
    private ?string $color = null;

    #[Column('daily_rate_multiplier')]
    private float $dailyRateMultiplier = 1.0;

    #[Column('requires_maintenance')]
    private bool $requiresMaintenance = false;

    #[Column('is_active')]
    private bool $isActive = true;

    #[Column('display_order')]
    private int $displayOrder = 0;

    #[Column('created_at')]
    private \DateTimeImmutable $createdAt;

    #[Column('updated_at')]
    private \DateTimeImmutable $updatedAt;

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
        $this->setName($name);
        $this->setSlug($slug);

        $this->description = $description;
        $this->icon = $icon;
        $this->color = $color;

        $this->setDailyRateMultiplier($dailyRateMultiplier);

        $this->requiresMaintenance = $requiresMaintenance;
        $this->isActive = $isActive;
        $this->setDisplayOrder($displayOrder);

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
            throw new \RuntimeException("L'ID ne peut pas être modifié");
        }

        $this->id = $id;

        return $this;
    }

    public function setName(string $name): self
    {
        $name = trim($name);

        if ($name === '') {
            throw new \InvalidArgumentException(
                'Le nom de la catégorie ne peut pas être vide'
            );
        }

        if (strlen($name) < 2) {
            throw new \InvalidArgumentException(
                'Le nom doit faire au moins 2 caractères'
            );
        }

        $this->name = $name;

        return $this;
    }

    public function setSlug(string $slug): self
    {
        $slug = strtolower(
            trim(
                preg_replace('/[^a-zA-Z0-9]+/', '-', $slug),
                '-'
            )
        );

        if ($slug === '') {
            throw new \InvalidArgumentException('Slug invalide');
        }

        $this->slug = $slug;

        return $this;
    }

    public function setDailyRateMultiplier(float $value): self
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(
                'Le multiplicateur ne peut pas être négatif'
            );
        }

        $this->dailyRateMultiplier = $value;

        return $this;
    }

    public function setDisplayOrder(int $value): self
    {
        $this->displayOrder = max(0, $value);

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