<?php

namespace BaseSystem\Repositories;

use BaseSystem\Foundation\Database\BaseModel;
use BaseSystem\Interfaces\BaseInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

abstract class BaseRepository implements BaseInterface
{
    /**
     * @var array
     */
    protected array $events = [];

    /**
     * @param BaseModel $model
     */
    public function __construct(protected BaseModel $model)
    {
    }

    /**
     * @param array $params
     * @param bool $paginate
     * @param array $attributes
     * @return LengthAwarePaginator|Collection
     */
    public function getList(array $params = [], bool $paginate = true, array $attributes = []): LengthAwarePaginator|Collection
    {
        $builder = $this->attachOrderByQuery($params);
        $filter = $this->getFilter($builder);

        $filter->attachFilterQuery($params);

        if (Arr::get($params, 'all') == 1) {
            $paginate = false;
        }

        $this->onBeforeResult($builder);

        if ($attributes) {
            $builder->select($attributes);
        }

        if ($paginate) {
            return $builder->paginate();
        }

        return $builder->get();
    }

    /**
     * @param array $params
     * @return Builder
     */
    protected function attachOrderByQuery(array $params = []): Builder
    {
        $builder = $this->model->newQuery();
        $orderColumn = Arr::get($params, 'sort_by', $this->model->defaultSortColumn());
        $orderBy = Arr::get($params, 'sort_order', $this->model->defaultSortOrder());
        $perPage = Arr::get($params, 'per_page', $this->model->getPerPage());

        $this->model->setPerPage($perPage);

        if (!in_array($orderColumn, $this->model->sortableColumns(), true)) {
            $orderColumn = $this->model->defaultSortColumn();
        }

        $orderColumn = $this->model->mapSortKey($orderColumn);

        if (!in_array($orderBy, ['asc', 'desc'])) {
            $orderBy = $this->model->defaultSortOrder();
        }

        if ($orderColumn) {
            $builder->orderBy($orderColumn, $orderBy);
        }

        return $builder;
    }

    /**
     * @param Builder $builder
     */
    abstract public function getFilter(Builder $builder);

    /**
     * @param Builder $builder
     */
    public function onBeforeResult(Builder $builder): void
    {
    }

    /**
     * @param int $id
     * @param array $attributes
     * @return bool
     */
    public function destroy(string $id, array $attributes = []): true
    {
        $model = $this->model->newQuery()->findOrFail($id);
        $this->dispatchEvent('deleting', $model);
        $model->delete();
        $this->dispatchEvent('deleted', $model);

        return true;
    }

    /**
     * @param $eventName
     * @param $model
     */
    protected function dispatchEvent($eventName, $model): void
    {
        if (array_key_exists($eventName, $this->events)) {
            Event::dispatch(new $this->events[$eventName]($model));
        }
    }

    /**
     * @param array $attributes
     * @return BaseModel
     */
    public function create(array $attributes): BaseModel
    {
        $model = $this->model->newInstance();
        $model->fill($attributes);
        $this->dispatchEvent('creating', $model);
        $model->save();
        $this->dispatchEvent('created', $model);
        return $model;
    }

    /**
     * @param string $id
     * @param array $attributes
     * @return BaseModel
     */
    public function update(string $id, array $attributes): BaseModel
    {
        $model = $this->model->newQuery()->findOrFail($id);
        $model->fill($attributes);
        $this->dispatchEvent('updating', $model);
        $model->save();
        $this->dispatchEvent('updated', $model);
        return $model;
    }


    /**
     * @param string $id
     * @param array $attributes
     * @return BaseModel
     */
    public function getById(string $id, array $attributes = []): BaseModel
    {
        return $this->model->newQuery()->findOrFail($id);
    }
}
