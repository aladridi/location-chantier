<?php

namespace Tests\Unit\Core\Container\Fixtures;

class ServiceWithOptionalParameter
{
    public function __construct(
        private string $value = 'default'
    ) {}

    public function getValue(): string
    {
        return $this->value;
    }
}
