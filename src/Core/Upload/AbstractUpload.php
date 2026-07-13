<?php
namespace App\Core\Upload;

use App\Core\Upload\Exceptions\UploadException;

abstract class AbstractUpload implements UploadInterface
{
    protected string $uploadDir;
    protected array $allowedMimeTypes = [];
    protected int $maxFileSize = 5242880; // 5MB par défaut
    protected bool $overwrite = false;

    public function __construct(string $uploadDir)
    {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->ensureDirectoryExists();
    }

    /**
     * Upload un fichier
     */
    public function upload(array $file, ?string $subDirectory = null): array
    {
        $this->validateFile($file);

        $filename = $this->generateFilename($file);
        $relativePath = $this->getRelativePath($filename, $subDirectory);
        $fullPath = $this->getFullPath($relativePath);

        $this->ensureDirectoryExists(dirname($fullPath));

        if (!$this->overwrite && file_exists($fullPath)) {
            $filename = $this->generateUniqueFilename($file);
            $relativePath = $this->getRelativePath($filename, $subDirectory);
            $fullPath = $this->getFullPath($relativePath);
        }

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw UploadException::uploadError('Impossible de déplacer le fichier');
        }

        // Optimiser l'image si c'est une image
        $this->optimizeFile($fullPath);

        return [
            'filename' => $filename,
            'path' => $relativePath,
            'full_path' => $fullPath,
            'size' => $file['size'],
            'mime_type' => $file['type'] ?? mime_content_type($fullPath),
            'url' => $this->getUrl($filename, $subDirectory)
        ];
    }

    /**
     * Supprime un fichier
     */
    public function delete(string $filename, ?string $subDirectory = null): bool
    {
        $fullPath = $this->getFullPath($this->getRelativePath($filename, $subDirectory));

        if (!file_exists($fullPath)) {
            return false;
        }

        return unlink($fullPath);
    }

    /**
     * Récupère l'URL d'un fichier
     */
    public function getUrl(string $filename, ?string $subDirectory = null): string
    {
        $path = $this->getRelativePath($filename, $subDirectory);
        return '/uploads/' . ltrim($path, '/');
    }

    /**
     * Récupère le chemin d'un fichier
     */
    public function getPath(string $filename, ?string $subDirectory = null): string
    {
        return $this->getFullPath($this->getRelativePath($filename, $subDirectory));
    }

    /**
     * Valide le fichier
     */
    protected function validateFile(array $file): void
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw UploadException::invalidFile('Fichier non valide');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw UploadException::uploadError($this->getUploadErrorMessage($file['error']));
        }

        if ($file['size'] > $this->maxFileSize) {
            throw UploadException::fileTooLarge(
                sprintf('Le fichier dépasse la taille maximale de %s', $this->formatSize($this->maxFileSize))
            );
        }

        if (!empty($this->allowedMimeTypes)) {
            $mimeType = $file['type'] ?? mime_content_type($file['tmp_name']);
            if (!in_array($mimeType, $this->allowedMimeTypes)) {
                throw UploadException::invalidType(
                    sprintf('Type de fichier non autorisé. Types autorisés: %s', implode(', ', $this->allowedMimeTypes))
                );
            }
        }
    }

    /**
     * Génère un nom de fichier unique
     */
    protected function generateFilename(array $file): string
    {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        return uniqid() . '_' . date('Ymd_His') . '.' . $extension;
    }

    /**
     * Génère un nom de fichier unique (sans conflit)
     */
    protected function generateUniqueFilename(array $file): string
    {
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $i = 1;
        do {
            $filename = uniqid() . '_' . date('Ymd_His') . '_' . $i . '.' . $extension;
            $i++;
        } while (file_exists($this->getFullPath($filename)));

        return $filename;
    }

    /**
     * Récupère le chemin relatif
     */
    protected function getRelativePath(string $filename, ?string $subDirectory = null): string
    {
        $path = '';
        if ($subDirectory) {
            $path = rtrim($subDirectory, '/') . '/';
        }
        return $path . $filename;
    }

    /**
     * Récupère le chemin complet
     */
    protected function getFullPath(string $relativePath): string
    {
        return $this->uploadDir . ltrim($relativePath, '/');
    }

    /**
     * Optimise le fichier (à surcharger)
     */
    protected function optimizeFile(string $filePath): void
    {
        // À surcharger selon les besoins
    }

    /**
     * Crée les répertoires si nécessaire
     */
    protected function ensureDirectoryExists(?string $path = null): void
    {
        $dir = $path ?? $this->uploadDir;
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                throw UploadException::directoryError('Impossible de créer le répertoire: ' . $dir);
            }
        }
    }

    /**
     * Formate la taille d'un fichier
     */
    protected function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Message d'erreur d'upload
     */
    protected function getUploadErrorMessage(int $errorCode): string
    {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par le serveur',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale autorisée par le formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
            UPLOAD_ERR_NO_TMP_DIR => 'Le répertoire temporaire est manquant',
            UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire le fichier sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté le téléchargement',
        ];
        return $messages[$errorCode] ?? 'Erreur inconnue lors du téléchargement';
    }
}