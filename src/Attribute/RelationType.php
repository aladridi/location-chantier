<?php

namespace App\Attribute;

enum RelationType: string
{
    case MANY_TO_ONE = 'manyToOne';
    case ONE_TO_MANY = 'oneToMany';
    case ONE_TO_ONE = 'oneToOne';
    case MANY_TO_MANY = 'manyToMany';
}