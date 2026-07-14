<?php
namespace App\Entity;

class EquipmentImage
{
    private ?int $id = null;
    private ?Equipment $equipment = null;
    private string $filename;
    private string $originalName;
    private string $path;
    private int $size;
    private string $mimeType;
    private ?int $width = null;
    private ?int $height = null;
    private ?string $altText = null;
    private ?string $title = null;
    private bool $isMain = false;
    private int $sortOrder = 0;
    private bool $isActive = true;
    private \DateTimeImmutable $createdAt;
    private \DateTimeImmutable $updatedAt;

    public function __construct(
        Equipment $equipment,
        string $filename,
        string $originalName,
        string $path,
        int $size,
        string $mimeType,
        ?int $width = null,
        ?int $height = null,
        ?string $altText = null,
        ?string $title = null,
        bool $isMain = false,
        int $sortOrder = 0
    ) {
        $this->equipment = $equipment;
        $this->filename = $filename;
        $this->originalName = $originalName;
        $this->path = $path;
        $this->size = $size;
        $this->mimeType = $mimeType;
        $this->width = $width;
        $this->height = $height;
        $this->altText = $altText;
        $this->title = $title;
        $this->isMain = $isMain;
        $this->sortOrder = $sortOrder;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters
    public function getId(): ?int { return $this->id; }

    public function getEquipment(): ?Equipment  // ✅ Rendre nullable
    {
        return $this->equipment;
    }

    public function getEquipmentId(): ?int
    {
        return $this->equipment ? $this->equipment->getId() : null;
    }

    public function getFilename(): string { return $this->filename; }
    public function getOriginalName(): string { return $this->originalName; }
    public function getPath(): string { return $this->path; }
    public function getSize(): int { return $this->size; }
    public function getMimeType(): string { return $this->mimeType; }
    public function getWidth(): ?int { return $this->width; }
    public function getHeight(): ?int { return $this->height; }
    public function getAltText(): ?string { return $this->altText; }
    public function getTitle(): ?string { return $this->title; }
    public function isMain(): bool { return $this->isMain; }
    public function getSortOrder(): int { return $this->sortOrder; }
    public function isActive(): bool { return $this->isActive; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    // Setters
    public function setId(int $id): self {
        if ($this->id !== null) {
            throw new \RuntimeException('L\'ID ne peut pas être modifié');
        }
        $this->id = $id;
        return $this;
    }

    public function setEquipment(Equipment $equipment): self {
        $this->equipment = $equipment;
        return $this;
    }

    public function setFilename(string $filename): self {
        $this->filename = $filename;
        return $this;
    }

    public function setOriginalName(string $originalName): self {
        $this->originalName = $originalName;
        return $this;
    }

    public function setPath(string $path): self {
        $this->path = $path;
        return $this;
    }

    public function setSize(int $size): self {
        $this->size = $size;
        return $this;
    }

    public function setMimeType(string $mimeType): self {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function setWidth(?int $width): self {
        $this->width = $width;
        return $this;
    }

    public function setHeight(?int $height): self {
        $this->height = $height;
        return $this;
    }

    public function setAltText(?string $altText): self {
        $this->altText = $altText;
        return $this;
    }

    public function setTitle(?string $title): self {
        $this->title = $title;
        return $this;
    }

    public function setIsMain(bool $isMain): self {
        $this->isMain = $isMain;
        return $this;
    }

    public function setSortOrder(int $sortOrder): self {
        $this->sortOrder = max(0, $sortOrder);
        return $this;
    }

    public function setIsActive(bool $isActive): self {
        $this->isActive = $isActive;
        return $this;
    }

    public function getSizeFormatted(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $size = $this->size;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }
        return round($size, 2) . ' ' . $units[$i];
    }

    public function getUrl(string $size = 'medium'): string
    {
        $sizes = ['thumbnail', 'medium', 'large'];
        if (!in_array($size, $sizes)) {
            $size = 'medium';
        }
        return '/uploads/equipment/' . $size . '/' . $this->filename;
    }

    public function getOriginalUrl(): string
    {
        return '/uploads/equipment/original/' . $this->filename;
    }





    // ✅ Ajout de la méthode getThumbnailUrl
    public function getThumbnailUrl(): string
    {
        return '/uploads/equipment/thumbnail/' . $this->filename;
    }

    // ✅ Ajout de la méthode getMediumUrl
    public function getMediumUrl(): string
    {
        return '/uploads/equipment/medium/' . $this->filename;
    }

    // ✅ Ajout de la méthode getLargeUrl
    public function getLargeUrl(): string
    {
        return '/uploads/equipment/large/' . $this->filename;
    }


    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'equipment_id' => $this->getEquipmentId(),
            'filename' => $this->filename,
            'original_name' => $this->originalName,
            'size' => $this->size,
            'size_formatted' => $this->getSizeFormatted(),
            'mime_type' => $this->mimeType,
            'width' => $this->width,
            'height' => $this->height,
            'alt_text' => $this->altText,
            'title' => $this->title,
            'is_main' => $this->isMain,
            'sort_order' => $this->sortOrder,
            'is_active' => $this->isActive,
            'urls' => [
                'thumbnail' => $this->getUrl('thumbnail'),
                'medium' => $this->getUrl('medium'),
                'large' => $this->getUrl('large'),
                'original' => $this->getOriginalUrl(),
            ],
            'created_at' => $this->createdAt->format('Y-m-d H:i:s'),
            'updated_at' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}