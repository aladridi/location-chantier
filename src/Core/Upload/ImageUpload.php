<?php

namespace App\Core\Upload;

class ImageUpload extends AbstractUpload
{
    protected array $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',
    ];

    protected array $thumbnails = [
        'thumbnail' => [
            'width'  => 150,
            'height' => 150,
            'crop'   => true,
        ],
        'medium' => [
            'width'  => 400,
            'height' => 400,
            'crop'   => false,
        ],
        'large' => [
            'width'  => 1200,
            'height' => 1200,
            'crop'   => false,
        ],
    ];

    public function __construct(string $uploadDir)
    {
        /*
         * Appelle le constructeur parent.
         * Seul le dossier racine uploads/ est créé ici.
         */
        parent::__construct($uploadDir);
    }

    /**
     * Upload l'image et génère ses différentes tailles.
     */
    public function upload(
        array $file,
        ?string $subDirectory = null
    ): array {
        $result = parent::upload(
            $file,
            $subDirectory
        );

        /*
         * Les dossiers sont créés dans le sous-répertoire demandé :
         *
         * uploads/equipment/thumbnail/
         * uploads/equipment/medium/
         * uploads/equipment/large/
         */
        $this->ensureThumbnailDirectories(
            $subDirectory
        );

        $this->generateThumbnails(
            $result['full_path'],
            $subDirectory,
            $result['filename']
        );

        return $result;
    }

    /**
     * Supprime l'image originale et ses miniatures.
     */
    public function delete(
        string $filename,
        ?string $subDirectory = null
    ): bool {
        $originalDeleted = parent::delete(
            $filename,
            $subDirectory
        );

        foreach ($this->thumbnails as $size => $config) {
            $thumbnailPath = $this->getThumbnailPath(
                $filename,
                $size,
                $subDirectory
            );

            if (is_file($thumbnailPath)) {
                unlink($thumbnailPath);
            }
        }

        return $originalDeleted;
    }

    /**
     * Retourne l'URL d'une miniature.
     */
    public function getThumbnailUrl(
        string $filename,
        string $size = 'medium',
        ?string $subDirectory = null
    ): string {
        if (!isset($this->thumbnails[$size])) {
            $size = 'medium';
        }

        return '/uploads/'
            . $this->normalizeSubDirectory($subDirectory)
            . $size
            . '/'
            . ltrim($filename, '/\\');
    }

    /**
     * Génère toutes les miniatures.
     */
    protected function generateThumbnails(
        string $sourcePath,
        ?string $subDirectory,
        string $filename
    ): void {
        $imageInfo = @getimagesize($sourcePath);

        /*
         * Les fichiers SVG sont autorisés à l'upload,
         * mais GD ne permet pas de générer leur miniature nativement.
         */
        if (!$imageInfo) {
            return;
        }

        $originalWidth = (int) $imageInfo[0];
        $originalHeight = (int) $imageInfo[1];
        $mimeType = $imageInfo['mime'] ?? null;

        if (
            $originalWidth <= 0 ||
            $originalHeight <= 0 ||
            !$mimeType
        ) {
            return;
        }

        foreach ($this->thumbnails as $size => $config) {
            $this->generateThumbnail(
                $sourcePath,
                $filename,
                $size,
                $config,
                $subDirectory,
                $originalWidth,
                $originalHeight,
                $mimeType
            );
        }
    }

    /**
     * Génère une miniature.
     */
    protected function generateThumbnail(
        string $sourcePath,
        string $filename,
        string $size,
        array $config,
        ?string $subDirectory,
        int $originalWidth,
        int $originalHeight,
        string $mimeType
    ): void {
        $maxWidth = (int) $config['width'];
        $maxHeight = (int) $config['height'];
        $crop = (bool) ($config['crop'] ?? false);

        $source = $this->createImageFromFile(
            $sourcePath,
            $mimeType
        );

        if (!$source) {
            return;
        }

        if ($crop) {
            $targetWidth = $maxWidth;
            $targetHeight = $maxHeight;

            $targetRatio = $targetWidth / $targetHeight;
            $sourceRatio = $originalWidth / $originalHeight;

            if ($sourceRatio > $targetRatio) {
                /*
                 * Image trop large horizontalement :
                 * on découpe à gauche et à droite.
                 */
                $sourceHeight = $originalHeight;
                $sourceWidth = (int) round(
                    $originalHeight * $targetRatio
                );

                $sourceX = (int) round(
                    ($originalWidth - $sourceWidth) / 2
                );

                $sourceY = 0;
            } else {
                /*
                 * Image trop haute :
                 * on découpe en haut et en bas.
                 */
                $sourceWidth = $originalWidth;
                $sourceHeight = (int) round(
                    $originalWidth / $targetRatio
                );

                $sourceX = 0;

                $sourceY = (int) round(
                    ($originalHeight - $sourceHeight) / 2
                );
            }
        } else {
            /*
             * L'image conserve son ratio.
             * Elle tient dans les dimensions maximales configurées.
             */
            $ratio = min(
                $maxWidth / $originalWidth,
                $maxHeight / $originalHeight,
                1
            );

            $targetWidth = max(
                1,
                (int) round($originalWidth * $ratio)
            );

            $targetHeight = max(
                1,
                (int) round($originalHeight * $ratio)
            );

            $sourceX = 0;
            $sourceY = 0;
            $sourceWidth = $originalWidth;
            $sourceHeight = $originalHeight;
        }

        $target = imagecreatetruecolor(
            $targetWidth,
            $targetHeight
        );

        if (!$target) {
            unset($source);
            return;
        }

        $this->preserveTransparency(
            $target,
            $mimeType
        );

        imagecopyresampled(
            $target,
            $source,
            0,
            0,
            $sourceX,
            $sourceY,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        $thumbnailPath = $this->getThumbnailPath(
            $filename,
            $size,
            $subDirectory
        );

        /*
         * Sécurité supplémentaire :
         * même si ensureThumbnailDirectories() n'a pas été appelé,
         * le dossier sera créé ici.
         */
        $this->ensureDirectoryExists(
            dirname($thumbnailPath)
        );

        $this->saveImage(
            $target,
            $thumbnailPath,
            $mimeType
        );

        unset($source, $target);
    }

    /**
     * Charge une image avec GD.
     */
    protected function createImageFromFile(
        string $path,
        string $mimeType
    ) {
        switch ($mimeType) {
            case 'image/jpeg':
                return @imagecreatefromjpeg($path);

            case 'image/png':
                return @imagecreatefrompng($path);

            case 'image/gif':
                return @imagecreatefromgif($path);

            case 'image/webp':
                return function_exists('imagecreatefromwebp')
                    ? @imagecreatefromwebp($path)
                    : null;

            default:
                return null;
        }
    }

    /**
     * Préserve la transparence des images compatibles.
     */
    protected function preserveTransparency(
        $image,
        string $mimeType
    ): void {
        if (
            !in_array(
                $mimeType,
                ['image/png', 'image/gif', 'image/webp'],
                true
            )
        ) {
            return;
        }

        imagealphablending($image, false);
        imagesavealpha($image, true);

        $transparent = imagecolorallocatealpha(
            $image,
            0,
            0,
            0,
            127
        );

        imagefilledrectangle(
            $image,
            0,
            0,
            imagesx($image),
            imagesy($image),
            $transparent
        );
    }

    /**
     * Enregistre la miniature.
     */
    protected function saveImage(
        $image,
        string $path,
        string $mimeType
    ): void {
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
                if (function_exists('imagewebp')) {
                    imagewebp($image, $path, 80);
                } else {
                    imagejpeg($image, $path, 85);
                }
                break;

            default:
                imagejpeg($image, $path, 85);
                break;
        }
    }

    /**
     * Retourne le chemin physique d'une miniature.
     */
    protected function getThumbnailPath(
        string $filename,
        string $size,
        ?string $subDirectory = null
    ): string {
        $relativePath = $this->normalizeSubDirectory(
            $subDirectory
        );

        $relativePath .= $size
            . '/'
            . ltrim($filename, '/\\');

        return $this->getFullPath($relativePath);
    }

    /**
     * Crée les dossiers des miniatures au bon emplacement.
     */
    protected function ensureThumbnailDirectories(
        ?string $subDirectory = null
    ): void {
        $baseRelativePath = $this->normalizeSubDirectory(
            $subDirectory
        );

        foreach ($this->thumbnails as $size => $config) {
            $directory = $this->getFullPath(
                $baseRelativePath . $size
            );

            $this->ensureDirectoryExists($directory);
        }
    }
}