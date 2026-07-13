<?php
namespace App\Core\Upload;

use App\Core\Upload\Exceptions\UploadException;

class ImageUpload extends AbstractUpload
{
    protected array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml'
    ];

    protected array $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'
    ];

    protected array $thumbnails = [
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
        'medium' => ['width' => 400, 'height' => 400, 'crop' => false],
        'large' => ['width' => 1200, 'height' => 1200, 'crop' => false],
    ];

    public function __construct(string $uploadDir)
    {
        parent::__construct($uploadDir);
        $this->ensureThumbnailDirectories();
    }

    /**
     * Upload une image avec génération des thumbnails
     */
    public function upload(array $file, ?string $subDirectory = null): array
    {
        $result = parent::upload($file, $subDirectory);

        // Générer les thumbnails
        $this->generateThumbnails($result['full_path'], $subDirectory, $result['filename']);

        return $result;
    }

    /**
     * Supprime une image et ses thumbnails
     */
    public function delete(string $filename, ?string $subDirectory = null): bool
    {
        $deleted = parent::delete($filename, $subDirectory);

        // Supprimer les thumbnails
        foreach ($this->thumbnails as $name => $config) {
            $thumbnailPath = $this->getThumbnailPath($filename, $name, $subDirectory);
            if (file_exists($thumbnailPath)) {
                unlink($thumbnailPath);
            }
        }

        return $deleted;
    }

    /**
     * Récupère l'URL d'un thumbnail
     */
    public function getThumbnailUrl(string $filename, string $size = 'medium', ?string $subDirectory = null): string
    {
        if (!isset($this->thumbnails[$size])) {
            return $this->getUrl($filename, $subDirectory);
        }

        $path = $this->getRelativePath($filename, $subDirectory);
        return '/uploads/' . $size . '/' . ltrim($path, '/');
    }

    /**
     * Génère les thumbnails
     */
    protected function generateThumbnails(string $sourcePath, ?string $subDirectory, string $filename): void
    {
        $imageInfo = @getimagesize($sourcePath);
        if (!$imageInfo) {
            return;
        }

        list($width, $height) = $imageInfo;
        $mimeType = $imageInfo['mime'];

        foreach ($this->thumbnails as $name => $config) {
            $this->generateThumbnail(
                $sourcePath,
                $filename,
                $name,
                $config,
                $subDirectory,
                $width,
                $height,
                $mimeType
            );
        }
    }

    /**
     * Génère un thumbnail
     */
    protected function generateThumbnail(
        string $sourcePath,
        string $filename,
        string $name,
        array $config,
        ?string $subDirectory,
        int $originalWidth,
        int $originalHeight,
        string $mimeType
    ): void {
        $targetWidth = $config['width'];
        $targetHeight = $config['height'];
        $crop = $config['crop'] ?? false;

        // Calculer les dimensions
        if ($crop) {
            $ratio = max($targetWidth / $originalWidth, $targetHeight / $originalHeight);
            $newWidth = intval($originalWidth * $ratio);
            $newHeight = intval($originalHeight * $ratio);
            $srcX = intval(($newWidth - $targetWidth) / 2);
            $srcY = intval(($newHeight - $targetHeight) / 2);
        } else {
            $ratio = min($targetWidth / $originalWidth, $targetHeight / $originalHeight);
            $newWidth = intval($originalWidth * $ratio);
            $newHeight = intval($originalHeight * $ratio);
            $srcX = 0;
            $srcY = 0;
        }

        // Créer l'image source
        $source = $this->createImageFromFile($sourcePath, $mimeType);
        if (!$source) {
            return;
        }

        // Créer l'image destination
        $target = imagecreatetruecolor($targetWidth, $targetHeight);

        // Gérer la transparence pour PNG et GIF
        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagecolortransparent($target, imagecolorallocatealpha($target, 0, 0, 0, 127));
            imagealphablending($target, false);
            imagesavealpha($target, true);
        }

        // Redimensionner
        if ($crop) {
            imagecopyresampled($target, $source, 0, 0, $srcX, $srcY, $targetWidth, $targetHeight, $newWidth, $newHeight);
        } else {
            imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);
        }

        // Sauvegarder
        $thumbnailPath = $this->getThumbnailPath($filename, $name, $subDirectory);
        $this->ensureDirectoryExists(dirname($thumbnailPath));

        $this->saveImage($target, $thumbnailPath, $mimeType);

        imagedestroy($source);
        imagedestroy($target);
    }

    /**
     * Crée une image depuis un fichier
     */
    protected function createImageFromFile(string $path, string $mimeType)
    {
        switch ($mimeType) {
            case 'image/jpeg':
                return imagecreatefromjpeg($path);
            case 'image/png':
                return imagecreatefrompng($path);
            case 'image/gif':
                return imagecreatefromgif($path);
            case 'image/webp':
                return imagecreatefromwebp($path);
            default:
                return null;
        }
    }

    /**
     * Sauvegarde une image
     */
    protected function saveImage($image, string $path, string $mimeType): void
    {
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($image, $path, 85);
                break;
            case 'image/png':
                imagepng($image, $path, 8);
                break;
            case 'image/gif':
                imagegif($image, $path);
                break;
            case 'image/webp':
                imagewebp($image, $path, 80);
                break;
            default:
                imagejpeg($image, $path, 85);
        }
    }

    /**
     * Récupère le chemin d'un thumbnail
     */
    protected function getThumbnailPath(string $filename, string $size, ?string $subDirectory = null): string
    {
        $relativePath = $this->getRelativePath($filename, $subDirectory);
        return $this->uploadDir . $size . '/' . ltrim($relativePath, '/');
    }

    /**
     * Crée les répertoires des thumbnails
     */
    protected function ensureThumbnailDirectories(): void
    {
        foreach ($this->thumbnails as $name => $config) {
            $this->ensureDirectoryExists($this->uploadDir . $name . '/');
        }
    }
}