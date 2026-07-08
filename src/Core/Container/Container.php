<?php
namespace App\Core\Container;

class Container implements ContainerInterface
{
    private array $definitions = [];
    private array $instances = [];
    private array $parameters = [];

    public function set(string $id, callable|object $definition): void
    {
        $this->definitions[$id] = $definition;
        // Si c'est déjà une instance, on la stocke directement
        if (is_object($definition) && !is_callable($definition)) {
            $this->instances[$id] = $definition;
        }
    }

    public function get(string $id): object
    {
        // 1. Si déjà instancié, retourner l'instance
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        // 2. Si définition existe, l'exécuter
        if (isset($this->definitions[$id])) {
            $definition = $this->definitions[$id];

            if (is_callable($definition)) {
                $instance = $definition($this);
            } else {
                $instance = $definition;
            }

            // Mettre en cache
            $this->instances[$id] = $instance;
            return $instance;
        }

        // 3. Auto-wiring : tentative de création automatique
        if (class_exists($id)) {
            $instance = $this->autoWire($id);
            $this->instances[$id] = $instance;
            return $instance;
        }

        throw new \RuntimeException("Service {$id} not found");
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id]) || class_exists($id);
    }

    public function setParameter(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }

    public function getParameter(string $name): mixed
    {
        if (!isset($this->parameters[$name])) {
            throw new \RuntimeException("Parameter {$name} not found");
        }
        return $this->parameters[$name];
    }

    /**
     * Auto-wiring : résolution automatique des dépendances
     */
    private function autoWire(string $className): object
    {
        $reflection = new \ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new \RuntimeException("Class {$className} is not instantiable");
        }

        $constructor = $reflection->getConstructor();

        if (!$constructor) {
            return new $className();
        }

        $parameters = $constructor->getParameters();
        $dependencies = [];

        foreach ($parameters as $parameter) {
            $type = $parameter->getType();

            if (!$type || $type->isBuiltin()) {
                throw new \RuntimeException("Cannot resolve parameter {$parameter->getName()}");
            }

            $dependencyName = $type->getName();
            $dependencies[] = $this->get($dependencyName);
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}