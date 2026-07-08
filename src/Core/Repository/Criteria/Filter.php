<?php
namespace App\Core\Repository\Criteria;

class Filter
{
    public function __construct(
        private string $field,
        private mixed $value,
        private string $operator = '='
    ) {}

    public function getField(): string
    {
        return $this->field;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'value' => $this->value,
            'operator' => $this->operator,
        ];
    }
}