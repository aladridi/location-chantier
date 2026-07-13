<?php
namespace App\Core\Upload\Exceptions;

class UploadException extends \RuntimeException
{
    public const INVALID_FILE = 1;
    public const UPLOAD_ERROR = 2;
    public const INVALID_TYPE = 3;
    public const FILE_TOO_LARGE = 4;
    public const DIRECTORY_ERROR = 5;
    public const FILE_NOT_FOUND = 6;

    public static function invalidFile(string $message = 'Fichier invalide'): self
    {
        return new self($message, self::INVALID_FILE);
    }

    public static function uploadError(string $message = 'Erreur lors de l\'upload'): self
    {
        return new self($message, self::UPLOAD_ERROR);
    }

    public static function invalidType(string $message = 'Type de fichier non autorisé'): self
    {
        return new self($message, self::INVALID_TYPE);
    }

    public static function fileTooLarge(string $message = 'Fichier trop volumineux'): self
    {
        return new self($message, self::FILE_TOO_LARGE);
    }

    public static function directoryError(string $message = 'Erreur de répertoire'): self
    {
        return new self($message, self::DIRECTORY_ERROR);
    }

    public static function fileNotFound(string $message = 'Fichier non trouvé'): self
    {
        return new self($message, self::FILE_NOT_FOUND);
    }
}