<?php

namespace BaseSystem\Foundation\Database;

use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    /**
     * @var string
     */
    protected string $defaultSortColumn = "name";

    /**
     * @var string
     */
    protected string $defaultSortOrder = "asc";

    /**
     * @var array
     */
    protected array $sortableColumns = [];

    /**
     * @var array
     */
    protected array $sortKeyMaps = [];

    /**
     * @var int
     */
    protected $perPage = 16;

    /**
     * @return array
     */
    public function sortableColumns(): array
    {
        return $this->sortableColumns;
    }

    /**
     * @return string
     */
    public function defaultSortColumn(): string
    {
        return $this->defaultSortColumn;
    }

    /**
     * @return string
     */
    public function defaultSortOrder(): string
    {
        return $this->defaultSortOrder;
    }

    /**
     * @param $key
     * @return string
     */
    public function mapSortKey($key): string
    {
        return $this->sortKeyMaps[$key] ?? $key;
    }
}
