<?php
namespace App\Core\Discovery;

final class ClassScanner
{
    public function findImplementations(
        string $directory,
        string $namespace,
        string $interface
    ): array {
        $classes = [];

        foreach (glob($directory . '/*.php') as $file) {
            $className = $namespace . '\\' . basename($file, '.php');

            if (!class_exists($className)) {
                continue;
            }

            $interfaces = class_implements($className);

            if ($interfaces && in_array($interface, $interfaces, true)) {
                $classes[] = $className;
            }
        }

        return $classes;
    }
}