<?php
namespace App\Service;

use App\Core\Upload\ImageUpload;
use App\Core\Upload\ImageOptimizer;
use App\Core\Upload\Exceptions\UploadException;
use App\Entity\Equipment;
use App\Entity\EquipmentImage;
use App\Repository\EquipmentImageRepository;

class ImageService
{
    protected ImageUpload $uploader;
    protected ImageOptimizer $optimizer;
    protected EquipmentImageRepository $imageRepository;
    protected string $basePath;

    public function __construct(
        ImageUpload $uploader,
        ImageOptimizer $optimizer,
        EquipmentImageRepository $imageRepository
    ) {
        $this->uploader = $uploader;
        $this->optimizer = $optimizer;
        $this->imageRepository = $imageRepository;
        $this->basePath = __DIR__ . '/../../public/uploads/equipment/';
    }

    /**
     * Upload une image pour un équipement
     */
    public function uploadForEquipment(Equipment $equipment, array $file, bool $isMain = false): EquipmentImage
    {
        // Upload du fichier
        $uploadResult = $this->uploader->upload($file, 'equipment');

        // Créer l'entité image
        $image = new EquipmentImage(
            $equipment,
            $uploadResult['filename'],
            $file['name'],
            $uploadResult['path'],
            $uploadResult['size'],
            $uploadResult['mime_type'],
            $uploadResult['width'] ?? null,
            $uploadResult['height'] ?? null,
            null, // alt_text
            null, // title
            $isMain,
            $this->getNextSortOrder($equipment->getId())
        );

        // Si c'est la première image, la définir comme principale
        if ($this->imageRepository->getCountByEquipment($equipment->getId()) === 0) {
            $image->setIsMain(true);
        }

        // Sauvegarder
        $this->imageRepository->save($image);

        return $image;
    }

    /**
     * Upload multiple d'images
     */
    public function uploadMultipleForEquipment(Equipment $equipment, array $files): array
    {
        $results = [];
        $errors = [];

        foreach ($files as $index => $file) {
            try {
                $isMain = ($index === 0 && $this->imageRepository->getCountByEquipment($equipment->getId()) === 0);
                $image = $this->uploadForEquipment($equipment, $file, $isMain);
                $results[] = $image->toArray();
            } catch (UploadException $e) {
                $errors[] = [
                    'index' => $index,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'success' => $results,
            'errors' => $errors
        ];
    }

    /**
     * Supprime une image
     */
    public function deleteImage(EquipmentImage $image): bool
    {
        // Supprimer les fichiers physiques
        $this->imageRepository->deletePhysicalFiles($image);

        // Supprimer l'entrée en base
        $this->imageRepository->delete($image->getId());

        return true;
    }

    /**
     * Supprime toutes les images d'un équipement
     */
    public function deleteAllImages(Equipment $equipment): void
    {
        $this->imageRepository->deleteByEquipment($equipment->getId());
    }

    /**
     * Définit l'image principale
     */
    public function setMainImage(int $imageId, int $equipmentId): bool
    {
        $image = $this->imageRepository->find($imageId);
        if (!$image || $image->getEquipment()->getId() !== $equipmentId) {
            return false;
        }

        $this->imageRepository->setMainImage($imageId, $equipmentId);
        return true;
    }

    /**
     * Réorganise les images
     */
    public function reorderImages(int $equipmentId, array $order): void
    {
        $this->imageRepository->reorder($equipmentId, $order);
    }

    /**
     * Récupère toutes les images d'un équipement
     */
    public function getEquipmentImages(int $equipmentId): array
    {
        return $this->imageRepository->findByEquipment($equipmentId);
    }

    /**
     * Récupère l'image principale d'un équipement
     */
    public function getEquipmentMainImage(int $equipmentId): ?EquipmentImage
    {
        return $this->imageRepository->findMainImage($equipmentId);
    }

    /**
     * Calcule le prochain ordre de tri
     */
    protected function getNextSortOrder(int $equipmentId): int
    {
        $count = $this->imageRepository->getCountByEquipment($equipmentId);
        return $count;
    }



    /**
     * Upload une image
     */
    public function upload(array $file, ?string $subDirectory = null): array
    {
        return $this->uploader->upload($file, $subDirectory);
    }

    /**
     * Supprime une image
     */
    public function delete(string $filename, ?string $subDirectory = null): bool
    {
        return $this->uploader->delete($filename, $subDirectory);
    }

    /**
     * Récupère l'URL d'une image
     */
    public function getUrl(string $filename, ?string $subDirectory = null): string
    {
        return $this->uploader->getUrl($filename, $subDirectory);
    }

    /**
     * Récupère l'URL d'un thumbnail
     */
    public function getThumbnailUrl(string $filename, string $size = 'medium', ?string $subDirectory = null): string
    {
        return $this->uploader->getThumbnailUrl($filename, $size, $subDirectory);
    }

    /**
     * Optimise une image
     */
    public function optimize(string $filePath): bool
    {
        return $this->optimizer->optimize($filePath);
    }

    /**
     * Upload multiple d'images
     */
    public function uploadMultiple(array $files, ?string $subDirectory = null): array
    {
        $results = [];
        $errors = [];

        foreach ($files as $index => $file) {
            try {
                $results[] = $this->upload($file, $subDirectory);
            } catch (UploadException $e) {
                $errors[$index] = $e->getMessage();
            }
        }

        return [
            'success' => $results,
            'errors' => $errors
        ];
    }

    /**
     * Supprime plusieurs images
     */
    public function deleteMultiple(array $filenames, ?string $subDirectory = null): array
    {
        $results = [];
        foreach ($filenames as $filename) {
            $results[$filename] = $this->delete($filename, $subDirectory);
        }
        return $results;
    }
}