<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use xmlshop\QueueMonitor\Repository\Interfaces\BaseRepositoryInterface;

abstract class BaseRepository implements BaseRepositoryInterface
{
    public function create(array $data = []): Model
    {
        return $this->model->newQuery()->create($data);
    }

    public function getCollection(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->get($columns);
    }

    public function getCollectionByIds(array $ids, array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->findMany($ids, $columns);
    }

    public function update(int $id, string $attribute = 'id', array $data = []): bool
    {
        if (!$locatedModel = $this->model->newQuery()->where($attribute, '=', $id)->first()) {
            return false;
        }

        return $locatedModel->fill($data)->save();
    }

    public function updateMany(array $ids, array $data): int
    {
        return $this->model->newQuery()->whereIn('id', $ids)->update($data);
    }

    public function delete($ids): int
    {
        return $this->model->newQuery()->destroy($ids);
    }

    public function findById(int $id, array $columns = ['*'], array $with = []): Model
    {
        return $this->model->newQuery()->with($with)->findOrFail($id, $columns);
    }

    public function findOneByAttribute(string $attribute, string $value, array $columns = ['*']): Model
    {
        return $this->model->newQuery()->where($attribute, '=', $value)->firstOrFail($columns);
    }

    public function getCollectionWhereIn(string $attribute, array $values, array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->whereIn($attribute, $values)->get($columns);
    }

    public function getCollectionWhereBetween(string $attribute, array $values, array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->whereBetween($attribute, $values)->get($columns);
    }

    public function count(): int
    {
        return $this->model->newQuery()->count();
    }

    public function load(int|string $id, array $columns = ['*']): Model
    {
        return $this->model->newQuery()->find($id, $columns);
    }

    public function loadRelations(Model $model, array $relations): void
    {
        $model->load($relations);
    }

    public function findByOrderBy(
        string $field,
        mixed $value,
        array $columns = ['*'],
        string $orderBy = 'id',
        string $orderDirection = 'DESC'
    ): Model {
        return $this->model::query()
            ->select($columns)
            ->orderBy($orderBy, strtoupper($orderDirection) === 'ASC' ? 'ASC' : 'DESC')
            ->where($field, '=', $value)
            ->first();
    }

    /**
     * Find records by attribute.
     */
    public function __call(string $name, array $arguments): mixed
    {
        return \call_user_func_array([$this->model, $name], $arguments);
    }
}
