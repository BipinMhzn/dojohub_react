<?php

namespace BaseSystem\Interfaces;


use BaseSystem\Foundation\Database\BaseModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface BaseInterface
{
    /**
     * @param array $attributes
     */
    public function create(array $attributes): BaseModel;

    /**
     * @param string $id
     * @param array $attributes
     */
    public function update(string $id, array $attributes): BaseModel;

    /**
     * @param string $id
     * @param array $attributes
     */
    public function getById(string $id, array $attributes = []): BaseModel;

    /**
     * @param array $params
     * @param bool $paginate
     * @param array $attributes
     */
    public function getList(array $params = [], bool $paginate = true, array $attributes = []): LengthAwarePaginator|Collection;

    /**
     * @param string $id
     * @param array $attributes
     */
    public function destroy(string $id, array $attributes = []): true;
}
