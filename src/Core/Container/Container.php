<?php

namespace App\Core\Container;

use App\Core\Discovery\ClassScanner;
use RuntimeException;

class Container implements ContainerInterface
{
    private array $definitions = [];
    private array $instances = [];
    private array $parameters = [];
    private ClassScanner $classScanner;

    /**
     * Répertoires et namespaces dans lesquels rechercher
     * les implémentations d'interfaces.
     */
    private array $discoveryPaths = [];

    public function __construct(?ClassScanner $classScanner = null)
    {
        $this->classScanner = $classScanner ?? new ClassScanner();

        $srcDirectory = dirname(__DIR__, 2);

        $this->registerDiscoveryPath(
            $srcDirectory . '/Core/Container',
            'App\\Core\\Container'
        );

        $this->registerDiscoveryPath(
            $srcDirectory . '/Core/Database',
            'App\\Core\\Database'
        );

        $this->registerDiscoveryPath(
            $srcDirectory . '/Core/EventDispatcher',
            'App\\Core\\EventDispatcher'
        );

        $this->registerDiscoveryPath(
            $srcDirectory . '/Core/Router',
            'App\\Core\\Router'
        );

        $this->registerDiscoveryPath(
            $srcDirectory . '/Repository',
            'App\\Repository'
        );

        $this->registerDiscoveryPath(
            $srcDirectory . '/Observer',
            'App\\Observer'
        );

        $this->registerDiscoveryPath(
            $srcDirectory . '/Service/PricingStrategy',
            'App\\Service\\PricingStrategy'
        );

        $this->registerDiscoveryPath(
            $srcDirectory . '/Service/PricingStrategy/Promotion',
            'App\\Service\\PricingStrategy\\Promotion'
        );
    }

    public function registerDiscoveryPath(
        string $directory,
        string $namespace
    ): void {
        $this->discoveryPaths[] = [
            'directory' => $directory,
            'namespace' => $namespace,
        ];
    }

    public function set(string $id, callable|object $definition): void
    {
        $this->definitions[$id] = $definition;

        if (is_object($definition) && !is_callable($definition)) {
            $this->instances[$id] = $definition;
        }
    }

    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            return $this->instances[$id];
        }

        if (isset($this->definitions[$id])) {
            $definition = $this->definitions[$id];

            $instance = is_callable($definition)
                ? $definition($this)
                : $definition;

            if (!is_object($instance)) {
                throw new RuntimeException(
                    "Definition for {$id} did not return an object"
                );
            }

            $this->instances[$id] = $instance;

            return $instance;
        }

        if (class_exists($id)) {
            $instance = $this->autoWire($id);
            $this->instances[$id] = $instance;

            return $instance;
        }

        if (interface_exists($id)) {
            $implementations = $this->findImplementations($id);

            if (count($implementations) === 1) {
                $instance = $this->get($implementations[0]);
                $this->instances[$id] = $instance;

                return $instance;
            }

            if (count($implementations) > 1) {
                throw new RuntimeException(
                    "Multiple implementations found for {$id}: "
                    . implode(', ', $implementations)
                );
            }
        }

        throw new RuntimeException("Service {$id} not found");
    }

    public function has(string $id): bool
    {
        return isset($this->definitions[$id])
            || class_exists($id)
            || interface_exists($id);
    }

    public function setParameter(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }

    public function getParameter(string $name): mixed
    {
        if (!array_key_exists($name, $this->parameters)) {
            throw new RuntimeException("Parameter {$name} not found");
        }

        return $this->parameters[$name];
    }

    private function autoWire(string $className): object
    {
        $reflection = new \ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new RuntimeException(
                "Class {$className} is not instantiable"
            );
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $className();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();

            if ($type === null || $type->isBuiltin()) {
                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                throw new RuntimeException(
                    "Cannot resolve parameter {$parameter->getName()} "
                    . "of {$className}"
                );
            }

            if (!$type instanceof \ReflectionNamedType) {
                throw new RuntimeException(
                    "Unsupported parameter type for {$parameter->getName()} "
                    . "of {$className}"
                );
            }

            $dependencyName = $type->getName();

            try {
                $dependencies[] = $this->get($dependencyName);
            } catch (RuntimeException $e) {
                if ($parameter->allowsNull()) {
                    $dependencies[] = null;
                    continue;
                }

                if ($parameter->isDefaultValueAvailable()) {
                    $dependencies[] = $parameter->getDefaultValue();
                    continue;
                }

                throw $e;
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }

    private function findImplementations(string $interface): array
    {
        $implementations = [];

        foreach ($this->discoveryPaths as $path) {
            if (!is_dir($path['directory'])) {
                continue;
            }

            $classes = $this->classScanner->findImplementations(
                $path['directory'],
                $path['namespace'],
                $interface
            );

            foreach ($classes as $class) {
                $reflection = new \ReflectionClass($class);

                if (!$reflection->isAbstract()) {
                    $implementations[] = $class;
                }
            }
        }

        return array_values(array_unique($implementations));
    }

    public function getImplementations(
        string $interface,
        string $directory,
        string $namespace
    ): array {
        $classes = $this->classScanner->findImplementations(
            $directory,
            $namespace,
            $interface
        );

        return array_values(array_filter(
            $classes,
            static fn (string $class): bool =>
            !(new \ReflectionClass($class))->isAbstract()
        ));
    }
}

