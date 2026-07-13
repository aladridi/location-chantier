<?php
namespace App\Core\Upload;

class ImageOptimizer
{
    protected int $quality = 85;
    protected int $maxWidth = 1920;
    protected int $maxHeight = 1920;

    public function __construct(int $quality = 85, int $maxWidth = 1920, int $maxHeight = 1920)
    {
        $this->quality = $quality;
        $this->maxWidth = $maxWidth;
        $this->maxHeight = $maxHeight;
    }

    /**
     * Optimise une image
     */
    public function optimize(string $filePath): bool
    {
        if (!file_exists($filePath)) {
            return false;
        }

        $imageInfo = @getimagesize($filePath);
        if (!$imageInfo) {
            return false;
        }

        list($width, $height) = $imageInfo;
        $mimeType = $imageInfo['mime'];

        // Redimensionner si trop grand
        if ($width > $this->maxWidth || $height > $this->maxHeight) {
            $this->resize($filePath, $mimeType, $width, $height);
        }

        // Optimiser la qualité
        $this->optimizeQuality($filePath, $mimeType);

        return true;
    }

    /**
     * Redimensionne une image
     */
    protected function resize(string $filePath, string $mimeType, int $width, int $height): void
    {
        $ratio = min($this->maxWidth / $width, $this->maxHeight / $height);
        $newWidth = intval($width * $ratio);
        $newHeight = intval($height * $ratio);

        $source = $this->createImage($filePath, $mimeType);
        $target = imagecreatetruecolor($newWidth, $newHeight);

        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
            imagecolortransparent($target, imagecolorallocatealpha($target, 0, 0, 0, 127));
            imagealphablending($target, false);
            imagesavealpha($target, true);
        }

        imagecopyresampled($target, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        $this->saveImage($target, $filePath, $mimeType);

        imagedestroy($source);
        imagedestroy($target);
    }

    /**
     * Optimise la qualité
     */
    protected function optimizeQuality(string $filePath, string $mimeType): void
    {
        $source = $this->createImage($filePath, $mimeType);
        if (!$source) {
            return;
        }

        $this->saveImage($source, $filePath, $mimeType, $this->quality);
        imagedestroy($source);
    }

    /**
     * Crée une image
     */
    protected function createImage(string $path, string $mimeType)
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
    protected function saveImage($image, string $path, string $mimeType, int $quality = 85): void
    {
        switch ($mimeType) {
            case 'image/jpeg':
                imagejpeg($image, $path, $quality);
                break;
            case 'image/png':
                imagepng($image, $path, 8);
                break;
            case 'image/gif':
                imagegif($image, $path);
                break;
            case 'image/webp':
                imagewebp($image, $path, $quality);
                break;
            default:
                imagejpeg($image, $path, $quality);
        }
    }
}