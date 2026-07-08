<?php
namespace App\Core\Repository\Criteria;

class Criteria
{
    private array $filters = [];
    private array $order = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct()
    {
        // Constructeur vide pour un fluent interface
    }

    public static function create(): self
    {
        return new self();
    }

    public function addFilter(Filter $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }

    public function where(string $field, mixed $value, string $operator = '='): self
    {
        $this->filters[] = new Filter($field, $value, $operator);
        return $this;
    }

    public function whereIn(string $field, array $values): self
    {
        $this->filters[] = new Filter($field, $values, 'IN');
        return $this;
    }

    public function whereBetween(string $field, mixed $min, mixed $max): self
    {
        $this->filters[] = new Filter($field, [$min, $max], 'BETWEEN');
        return $this;
    }

    public function whereLike(string $field, string $value): self
    {
        $this->filters[] = new Filter($field, $value, 'LIKE');
        return $this;
    }

    public function orderBy(string $field, string $direction = 'ASC'): self
    {
        $this->order[$field] = $direction;
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getOrder(): array
    {
        return $this->order;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function toArray(): array
    {
        return [
            'filters' => array_map(fn($f) => $f->toArray(), $this->filters),
            'order' => $this->order,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }
}