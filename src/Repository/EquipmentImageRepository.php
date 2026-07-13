<?php
namespace App\Repository;

use App\Entity\EquipmentImage;
use App\Core\Repository\AbstractRepository;

class EquipmentImageRepository extends AbstractRepository
{
    protected function initialize(): void
    {
        $this->tableName = 'equipment_images';
        $this->entityClass = EquipmentImage::class;
    }

    public function findByEquipment(int $equipmentId): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE equipment_id = :equipment_id 
                AND is_active = 1
                ORDER BY is_main DESC, sort_order ASC, created_at ASC";

        $results = $this->db->query($sql, ['equipment_id' => $equipmentId]);
        return $this->hydrateMultiple($results);
    }

    public function findMainImage(int $equipmentId): ?EquipmentImage
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE equipment_id = :equipment_id 
                AND is_main = 1 
                AND is_active = 1 
                LIMIT 1";

        $results = $this->db->query($sql, ['equipment_id' => $equipmentId]);

        if (empty($results)) {
            return null;
        }

        return $this->hydrate($results[0]);
    }

    public function setMainImage(int $imageId, int $equipmentId): void
    {
        // Réinitialiser toutes les images de l'équipement
        $this->db->execute(
            "UPDATE {$this->tableName} SET is_main = 0 WHERE equipment_id = ?",
            [$equipmentId]
        );

        // Définir l'image principale
        $this->db->execute(
            "UPDATE {$this->tableName} SET is_main = 1 WHERE id = ? AND equipment_id = ?",
            [$imageId, $equipmentId]
        );
    }

    public function reorder(int $equipmentId, array $order): void
    {
        foreach ($order as $imageId => $sortOrder) {
            $this->db->execute(
                "UPDATE {$this->tableName} SET sort_order = ? WHERE id = ? AND equipment_id = ?",
                [$sortOrder, $imageId, $equipmentId]
            );
        }
    }

    public function deleteByEquipment(int $equipmentId): void
    {
        $images = $this->findByEquipment($equipmentId);

        foreach ($images as $image) {
            // Supprimer physiquement les fichiers
            $this->deletePhysicalFiles($image);
        }

        // Supprimer les entrées en base
        $this->db->execute(
            "DELETE FROM {$this->tableName} WHERE equipment_id = ?",
            [$equipmentId]
        );
    }

    public function deletePhysicalFiles(EquipmentImage $image): void
    {
        $basePath = __DIR__ . '/../../public/uploads/equipment/';
        $sizes = ['original', 'large', 'medium', 'thumbnail'];

        foreach ($sizes as $size) {
            $filePath = $basePath . $size . '/' . $image->getFilename();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }
    }

    public function getCountByEquipment(int $equipmentId): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->tableName} 
                WHERE equipment_id = :equipment_id AND is_active = 1";

        $result = $this->db->query($sql, ['equipment_id' => $equipmentId]);
        return (int) ($result[0]['count'] ?? 0);
    }
}