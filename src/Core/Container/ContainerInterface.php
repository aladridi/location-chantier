<?php
namespace App\Core\Container;

interface ContainerInterface
{
    public function set(string $id, callable|object $definition): void;
    public function get(string $id): object;
    public function has(string $id): bool;
    public function setParameter(string $name, mixed $value): void;
    public function getParameter(string $name): mixed;
}