<?php

namespace App\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public readonly string $name
    ) {
    }
}