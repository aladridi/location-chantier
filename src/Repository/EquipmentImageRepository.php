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

    /**
     * ✅ Surcharge de save pour gérer correctement les champs
     * Cette méthode remplace celle du parent pour éviter les problèmes de mapping
     */
    public function save(object $entity): void
    {
        if (!$entity instanceof EquipmentImage) {
            throw new \InvalidArgumentException('Entity must be instance of EquipmentImage');
        }

        $image = $entity;
        $id = $image->getId();

        if ($id) {
            // Update
            $sql = "UPDATE {$this->tableName} SET 
                        equipment_id = :equipment_id,
                        filename = :filename,
                        original_name = :original_name,
                        path = :path,
                        size = :size,
                        mime_type = :mime_type,
                        width = :width,
                        height = :height,
                        alt_text = :alt_text,
                        title = :title,
                        is_main = :is_main,
                        sort_order = :sort_order,
                        is_active = :is_active
                    WHERE id = :id";

            $this->db->execute($sql, [
                'id' => $id,
                'equipment_id' => $image->getEquipment()->getId(),
                'filename' => $image->getFilename(),
                'original_name' => $image->getOriginalName(),
                'path' => $image->getPath(),
                'size' => $image->getSize(),
                'mime_type' => $image->getMimeType(),
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
                'alt_text' => $image->getAltText(),
                'title' => $image->getTitle(),
                'is_main' => $image->isMain() ? 1 : 0,
                'sort_order' => $image->getSortOrder(),
                'is_active' => $image->isActive() ? 1 : 0,
            ]);
        } else {
            // Insert
            $sql = "INSERT INTO {$this->tableName} 
                        (equipment_id, filename, original_name, path, size, mime_type, 
                         width, height, alt_text, title, is_main, sort_order, is_active) 
                    VALUES 
                        (:equipment_id, :filename, :original_name, :path, :size, :mime_type,
                         :width, :height, :alt_text, :title, :is_main, :sort_order, :is_active)";

            $this->db->execute($sql, [
                'equipment_id' => $image->getEquipment()->getId(),
                'filename' => $image->getFilename(),
                'original_name' => $image->getOriginalName(),
                'path' => $image->getPath(),
                'size' => $image->getSize(),
                'mime_type' => $image->getMimeType(),
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
                'alt_text' => $image->getAltText(),
                'title' => $image->getTitle(),
                'is_main' => $image->isMain() ? 1 : 0,
                'sort_order' => $image->getSortOrder(),
                'is_active' => $image->isActive() ? 1 : 0,
            ]);

            // Récupérer l'ID généré
            $lastId = (int) $this->db->lastInsertId();
            if ($lastId) {
                $reflection = new \ReflectionClass($entity);
                if ($reflection->hasProperty('id')) {
                    $property = $reflection->getProperty('id');
                    $property->setValue($entity, $lastId);
                }
            }
        }
    }

    /**
     * Trouve les images d'un équipement
     */
    public function findByEquipment(int $equipmentId): array
    {
        $sql = "SELECT * FROM {$this->tableName} 
                WHERE equipment_id = :equipment_id 
                AND is_active = 1
                ORDER BY is_main DESC, sort_order ASC, created_at ASC";

        $results = $this->db->query($sql, ['equipment_id' => $equipmentId]);
        return $this->hydrateMultiple($results);
    }

    /**
     * Trouve l'image principale d'un équipement
     */
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

    /**
     * Définit l'image principale
     */
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

    /**
     * Réorganise les images
     */
    public function reorder(int $equipmentId, array $order): void
    {
        foreach ($order as $imageId => $sortOrder) {
            $this->db->execute(
                "UPDATE {$this->tableName} SET sort_order = ? WHERE id = ? AND equipment_id = ?",
                [$sortOrder, $imageId, $equipmentId]
            );
        }
    }

    /**
     * Supprime toutes les images d'un équipement
     */
    public function deleteByEquipment(int $equipmentId): void
    {
        $images = $this->findByEquipment($equipmentId);

        foreach ($images as $image) {
            $this->deletePhysicalFiles($image);
        }

        $this->db->execute(
            "DELETE FROM {$this->tableName} WHERE equipment_id = ?",
            [$equipmentId]
        );
    }

    /**
     * Supprime les fichiers physiques d'une image
     */
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

    /**
     * Compte les images d'un équipement
     */
    public function getCountByEquipment(int $equipmentId): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->tableName} 
                WHERE equipment_id = :equipment_id AND is_active = 1";

        $result = $this->db->query($sql, ['equipment_id' => $equipmentId]);
        return (int) ($result[0]['count'] ?? 0);
    }

    /**
     * Supprime une image (surcharge pour supprimer aussi les fichiers)
     */
    public function delete(int $id): void
    {
        // Récupérer l'image avant de la supprimer
        $image = $this->find($id);
        if ($image) {
            $this->deletePhysicalFiles($image);
        }

        parent::delete($id);
    }

    /**
     * Hydrate une image (surcharge pour gérer l'équipement)
     */
    protected function hydrate(array $data): object
    {
        // Si equipment_id est présent, récupérer l'équipement
        if (isset($data['equipment_id'])) {
            $equipmentRepo = new EquipmentRepository($this->db);
            $equipment = $equipmentRepo->find($data['equipment_id']);
            if ($equipment) {
                $data['equipment'] = $equipment;
            }
            unset($data['equipment_id']);
        }

        return parent::hydrate($data);
    }
}