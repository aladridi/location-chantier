<?php

namespace Tests\Unit\Core\Container\Fixtures;

class ServiceWithOptionalDependency
{
    public function __construct(
        private RequiredDependency $dependency,
        private string $value = 'default'
    ) {}

    public function getDependency(): RequiredDependency
    {
        return $this->dependency;
    }

    public function getValue(): string
    {
        return $this->value;
    }
}

