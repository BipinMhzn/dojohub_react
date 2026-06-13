<?php

namespace BaseSystem\Foundation\Database;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    protected string $defaultSortColumn = 'name';

    protected string $defaultSortOrder = 'asc';

    protected array $sortableColumns = [];

    protected array $sortKeyMaps = [];

    /**
     * @var int
     */
    protected $perPage = 50;

    public function sortableColumns(): array
    {
        return $this->sortableColumns;
    }

    public function defaultSortColumn(): string
    {
        return $this->defaultSortColumn;
    }

    public function defaultSortOrder(): string
    {
        return $this->defaultSortOrder;
    }

    public function mapSortKey($key): string
    {
        return $this->sortKeyMaps[$key] ?? $key;
    }
}
