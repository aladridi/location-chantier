<?php

namespace Tests\Unit\Core\Container\Fixtures;

class ServiceWithNullableDependency
{

    public function __construct(
        private ?UnresolvableDependency $dependency
    ) {}

    public function getDependency(): ?UnresolvableDependency
    {
        return $this->dependency;
    }
}
