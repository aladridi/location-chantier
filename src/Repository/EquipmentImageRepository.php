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
     * ✅ Récupère une image par son ID avec l'équipement chargé
     */
    public function find(int $id): ?object
    {
        $sql = "SELECT * FROM {$this->tableName} WHERE id = :id";
        $result = $this->db->query($sql, ['id' => $id]);

        if (empty($result)) {
            return null;
        }

        return $this->hydrate($result[0]);
    }

    /**
     * ✅ Trouve les images d'un équipement
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
     * ✅ Récupère les images principales pour plusieurs équipements
     */
    public function findMainImagesForEquipment(array $equipmentIds): array
    {
        // ✅ Vérifier si le tableau est vide
        if (empty($equipmentIds)) {
            return [];
        }

        // ✅ Filtrer les IDs null ou invalides
        $validIds = array_filter($equipmentIds, function($id) {
            return $id !== null && $id > 0;
        });

        if (empty($validIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($validIds), '?'));

        $sql = "SELECT * FROM {$this->tableName} 
                WHERE equipment_id IN ({$placeholders}) 
                AND is_main = 1 
                AND is_active = 1";

        $results = $this->db->query($sql, $validIds);

        $images = [];
        foreach ($results as $row) {
            $image = $this->hydrate($row);
            $equipmentId = $image->getEquipmentId();
            if ($equipmentId !== null) {
                $images[$equipmentId] = $image;
            }
        }

        return $images;
    }




    /**
     * Définit l'image principale
     */
    public function findMainImage(int $equipmentId): ?EquipmentImage
    {
        // ✅ Vérifier que l'ID est valide
        if ($equipmentId <= 0) {
            return null;
        }

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
        // ✅ Vérifier que l'ID est valide
        if ($equipmentId <= 0) {
            return 0;
        }

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
     * ✅ Surcharge de hydrate pour charger l'équipement
     */
    protected function hydrate(array $data): object
    {
        $reflection = new \ReflectionClass($this->entityClass);
        $entity = $reflection->newInstanceWithoutConstructor();

        foreach ($data as $field => $value) {

            // Trouver la propriété liée à la colonne SQL
            $property = $this->findPropertyByColumn($reflection, $field);

            if (!$property) {
                continue;
            }

            // Gérer les valeurs nulles
            if ($value === null) {
                $property->setValue($entity, null);
                continue;
            }


            /*
             * Gestion des relations
             * Exemple :
             * equipment_id => Equipment
             */
            $relationAttributes = $property->getAttributes(\App\Attribute\Relation::class);

            if (!empty($relationAttributes)) {

                $relation = $relationAttributes[0]->newInstance();

                $repository = $this->getRepository($relation->targetEntity);

                $relatedEntity = $repository->find((int) $value);

                $property->setValue($entity, $relatedEntity);

                continue;
            }


            /*
             * Gestion des types PHP natifs
             */
            $type = $property->getType();

            if ($type instanceof \ReflectionNamedType) {

                $typeName = $type->getName();

                // Enum PHP
                if (enum_exists($typeName)) {
                    $value = $typeName::tryFrom($value);
                }

                // DateTimeImmutable
                elseif ($typeName === \DateTimeImmutable::class) {
                    $value = new \DateTimeImmutable($value);
                }

                // Boolean SQL (0/1)
                elseif ($typeName === 'bool') {
                    $value = (bool) $value;
                }

                // Integer SQL
                elseif ($typeName === 'int') {
                    $value = (int) $value;
                }

                // Float SQL
                elseif ($typeName === 'float') {
                    $value = (float) $value;
                }
            }


            $property->setValue($entity, $value);
        }

        return $entity;
    }
    /**
     * ✅ Définit l'image principale
     */
    public function setMainImage(int $imageId, int $equipmentId): void
    {
        // ✅ Vérifier que les IDs sont valides
        if ($imageId <= 0 || $equipmentId <= 0) {
            throw new \InvalidArgumentException("IDs invalides");
        }

        // ✅ Réinitialiser toutes les images de l'équipement
        $this->db->execute(
            "UPDATE {$this->tableName} SET is_main = 0 WHERE equipment_id = ?",
            [$equipmentId]
        );

        // ✅ Définir l'image principale
        $affected = $this->db->execute(
            "UPDATE {$this->tableName} SET is_main = 1 WHERE id = ? AND equipment_id = ?",
            [$imageId, $equipmentId]
        );

        if ($affected === 0) {
            throw new \RuntimeException("Impossible de définir l'image #{$imageId} comme principale");
        }
    }

    protected function mapFieldToProperty(string $field): string
    {
        return match ($field) {
            'equipment_id' => 'id',
            default => parent::mapFieldToProperty($field),
        };
    }
}