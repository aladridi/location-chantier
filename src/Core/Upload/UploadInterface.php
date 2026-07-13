<?php
namespace App\Core\Upload;

interface UploadInterface
{
    public function upload(array $file, ?string $subDirectory = null): array;
    public function delete(string $filename, ?string $subDirectory = null): bool;
    public function getUrl(string $filename, ?string $subDirectory = null): string;
    public function getPath(string $filename, ?string $subDirectory = null): string;
}