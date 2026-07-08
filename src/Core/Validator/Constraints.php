<?php
namespace App\Core\Validator\Constraints;

#[\Attribute]
class NotBlank
{
    public function __construct(
        public string $message = 'Cette valeur ne peut pas être vide'
    ) {}
}

#[\Attribute]
class Length
{
    public function __construct(
        public int $min,
        public int $max,
        public string $message = 'La longueur doit être entre {min} et {max} caractères'
    ) {}
}

#[\Attribute]
class Email
{
    public function __construct(
        public string $message = 'Email invalide'
    ) {}
}

#[\Attribute]
class Positive
{
    public function __construct(
        public string $message = 'La valeur doit être positive'
    ) {}
}