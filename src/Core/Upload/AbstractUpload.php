<?php

namespace App\Core\Upload;

use App\Core\Upload\Exceptions\UploadException;

abstract class AbstractUpload implements UploadInterface
{
    protected string $uploadDir;

    protected array $allowedMimeTypes = [];

    protected int $maxFileSize = 5242880; // 5 Mo par défaut

    protected bool $overwrite = false;

    public function __construct(string $uploadDir)
    {
        $this->uploadDir = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR;

        $this->ensureDirectoryExists($this->uploadDir);
    }

    /**
     * Upload un fichier.
     */
    public function upload(array $file, ?string $subDirectory = null): array
    {
        $this->validateFile($file);

        $filename = $this->generateFilename($file);
        $relativePath = $this->getRelativePath($filename, $subDirectory);
        $fullPath = $this->getFullPath($relativePath);

        // Création du sous-dossier principal si nécessaire.
        $this->ensureDirectoryExists(dirname($fullPath));

        if (!$this->overwrite && file_exists($fullPath)) {
            $filename = $this->generateUniqueFilename(
                $file,
                $subDirectory
            );

            $relativePath = $this->getRelativePath(
                $filename,
                $subDirectory
            );

            $fullPath = $this->getFullPath($relativePath);
        }

        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            throw UploadException::uploadError(
                'Impossible de déplacer le fichier téléchargé.'
            );
        }

        $this->optimizeFile($fullPath);

        $mimeType = mime_content_type($fullPath);

        return [
            'filename'  => $filename,
            'path'      => $relativePath,
            'full_path' => $fullPath,
            'size'      => filesize($fullPath),
            'mime_type' => $mimeType ?: ($file['type'] ?? null),
            'url'       => $this->getUrl($filename, $subDirectory),
        ];
    }

    /**
     * Supprime un fichier.
     */
    public function delete(
        string $filename,
        ?string $subDirectory = null
    ): bool {
        $relativePath = $this->getRelativePath(
            $filename,
            $subDirectory
        );

        $fullPath = $this->getFullPath($relativePath);

        if (!file_exists($fullPath)) {
            return false;
        }

        return unlink($fullPath);
    }

    /**
     * Récupère l'URL publique d'un fichier.
     */
    public function getUrl(
        string $filename,
        ?string $subDirectory = null
    ): string {
        $relativePath = $this->getRelativePath(
            $filename,
            $subDirectory
        );

        return '/uploads/' . ltrim(
                str_replace('\\', '/', $relativePath),
                '/'
            );
    }

    /**
     * Récupère le chemin physique complet d'un fichier.
     */
    public function getPath(
        string $filename,
        ?string $subDirectory = null
    ): string {
        return $this->getFullPath(
            $this->getRelativePath(
                $filename,
                $subDirectory
            )
        );
    }

    /**
     * Valide le fichier envoyé.
     */
    protected function validateFile(array $file): void
    {
        if (
            !isset($file['tmp_name']) ||
            !is_uploaded_file($file['tmp_name'])
        ) {
            throw UploadException::invalidFile(
                'Fichier non valide.'
            );
        }

        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($error !== UPLOAD_ERR_OK) {
            throw UploadException::uploadError(
                $this->getUploadErrorMessage($error)
            );
        }

        $fileSize = (int) ($file['size'] ?? 0);

        if ($fileSize > $this->maxFileSize) {
            throw UploadException::fileTooLarge(
                sprintf(
                    'Le fichier dépasse la taille maximale de %s.',
                    $this->formatSize($this->maxFileSize)
                )
            );
        }

        if (!empty($this->allowedMimeTypes)) {
            /*
             * Ne pas faire confiance uniquement à $file['type'],
             * car cette valeur vient du navigateur.
             */
            $mimeType = mime_content_type($file['tmp_name']);

            if (
                !$mimeType ||
                !in_array($mimeType, $this->allowedMimeTypes, true)
            ) {
                throw UploadException::invalidType(
                    sprintf(
                        'Type de fichier non autorisé. Types autorisés : %s',
                        implode(', ', $this->allowedMimeTypes)
                    )
                );
            }
        }
    }

    /**
     * Génère un nom de fichier.
     */
    protected function generateFilename(array $file): string
    {
        $extension = strtolower(
            pathinfo($file['name'] ?? '', PATHINFO_EXTENSION)
        );

        $filename = uniqid('', true)
            . '_'
            . date('Ymd_His');

        if ($extension !== '') {
            $filename .= '.' . $extension;
        }

        /*
         * uniqid('', true) ajoute un point.
         * On le remplace pour garder un nom propre.
         */
        return str_replace('.', '', $filename);
    }

    /**
     * Génère un nom ne provoquant pas de conflit.
     */
    protected function generateUniqueFilename(
        array $file,
        ?string $subDirectory = null
    ): string {
        $extension = strtolower(
            pathinfo($file['name'] ?? '', PATHINFO_EXTENSION)
        );

        do {
            $filename = str_replace(
                '.',
                '',
                uniqid('', true)
            );

            $filename .= '_' . date('Ymd_His');

            if ($extension !== '') {
                $filename .= '.' . $extension;
            }

            $relativePath = $this->getRelativePath(
                $filename,
                $subDirectory
            );

            $fullPath = $this->getFullPath($relativePath);
        } while (file_exists($fullPath));

        return $filename;
    }

    /**
     * Construit le chemin relatif du fichier.
     *
     * Exemple :
     * equipment/image.png
     */
    protected function getRelativePath(
        string $filename,
        ?string $subDirectory = null
    ): string {
        return $this->normalizeSubDirectory($subDirectory)
            . ltrim($filename, '/\\');
    }

    /**
     * Nettoie et sécurise le sous-répertoire.
     *
     * Exemple :
     * equipment devient equipment/
     */
    protected function normalizeSubDirectory(
        ?string $subDirectory
    ): string {
        if (
            $subDirectory === null ||
            trim($subDirectory) === ''
        ) {
            return '';
        }

        $subDirectory = str_replace(
            '\\',
            '/',
            trim($subDirectory)
        );

        $subDirectory = trim($subDirectory, '/');

        // Réduction des doubles slashs.
        $subDirectory = preg_replace(
            '#/+#',
            '/',
            $subDirectory
        );

        if (
            $subDirectory === '' ||
            str_contains($subDirectory, "\0") ||
            preg_match('#(^|/)\.\.(/|$)#', $subDirectory)
        ) {
            throw UploadException::directoryError(
                'Sous-répertoire invalide.'
            );
        }

        return $subDirectory . '/';
    }

    /**
     * Construit le chemin physique complet.
     */
    protected function getFullPath(string $relativePath): string
    {
        $relativePath = str_replace(
            ['/', '\\'],
            DIRECTORY_SEPARATOR,
            $relativePath
        );

        return $this->uploadDir
            . ltrim($relativePath, DIRECTORY_SEPARATOR);
    }

    /**
     * Optimise le fichier.
     *
     * Cette méthode peut être surchargée dans les classes enfants.
     */
    protected function optimizeFile(string $filePath): void
    {
        // Aucune optimisation par défaut.
    }

    /**
     * Crée un dossier s'il n'existe pas.
     */
    protected function ensureDirectoryExists(
        ?string $path = null
    ): void {
        $directory = $path ?? $this->uploadDir;

        if (is_dir($directory)) {
            return;
        }

        /*
         * Le deuxième is_dir évite une erreur dans le cas où
         * deux requêtes créeraient simultanément le dossier.
         */
        if (
            !mkdir($directory, 0755, true) &&
            !is_dir($directory)
        ) {
            throw UploadException::directoryError(
                'Impossible de créer le répertoire : '
                . $directory
            );
        }
    }

    /**
     * Formate une taille en octets.
     */
    protected function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        $size = $bytes;

        while (
            $size >= 1024 &&
            $index < count($units) - 1
        ) {
            $size /= 1024;
            $index++;
        }

        return round($size, 2) . ' ' . $units[$index];
    }

    /**
     * Retourne le message correspondant à l'erreur PHP.
     */
    protected function getUploadErrorMessage(
        int $errorCode
    ): string {
        $messages = [
            UPLOAD_ERR_INI_SIZE =>
                'Le fichier dépasse la taille maximale autorisée par le serveur.',

            UPLOAD_ERR_FORM_SIZE =>
                'Le fichier dépasse la taille maximale autorisée par le formulaire.',

            UPLOAD_ERR_PARTIAL =>
                'Le fichier n’a été que partiellement téléchargé.',

            UPLOAD_ERR_NO_FILE =>
                'Aucun fichier n’a été téléchargé.',

            UPLOAD_ERR_NO_TMP_DIR =>
                'Le répertoire temporaire est manquant.',

            UPLOAD_ERR_CANT_WRITE =>
                'Impossible d’écrire le fichier sur le disque.',

            UPLOAD_ERR_EXTENSION =>
                'Une extension PHP a arrêté le téléchargement.',
        ];

        return $messages[$errorCode]
            ?? 'Erreur inconnue lors du téléchargement.';
    }
}