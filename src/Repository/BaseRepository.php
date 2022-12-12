<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    protected Model $model;

    abstract public function getModelName(): string;

    public function __construct()
    {
        $this->makeModel();
    }

    public function save(Model $model): bool
    {
        return $model->save();
    }

    public function makeModel(): void
    {
        $model = app($this->getModelName());

        if (!$model instanceof Model) {
            throw new \Exception("Class $model must be an instance of Illuminate\Database\Eloquent\Model");
        }

        $this->model = $model;
    }

    public function create(array $data = []): Model
    {
        return $this->model->create($data);
    }

    public function getFillableFields(): array
    {
        $fillable = $this->model->getFillable();
        if (\is_callable([$this->model, 'getNotFillable'])) {
            $fillable = array_diff($fillable, $this->model->getNotFillable());
        }
        return $fillable;
    }

    public function getCollection(array $columns = ['*'])
    {
        return $this->model::get($columns);
    }

    public function getCollectionByIds(array $ids, array $columns = ['*'])
    {
        return $this->model::findMany($ids, $columns);
    }

    public function update(int $id, string $attribute = 'id', array $data = []): bool
    {
        $model = $this->model::where($attribute, '=', $id)->first();

        if (!$model) {
            return false;
        }

        $model->fill($data);

        return $model->save();
    }

    public function updateMany(array $ids, array $data): int
    {
        return $this->model::whereIn('id', $ids)->update($data);
    }

    public function delete($ids): int
    {
        return $this->model::destroy($ids);
    }

    public function findById(int $id, array $columns = ['*'], array $with = [])
    {
        return $this->model::with($with)->findOrFail($id, $columns);
    }

    public function findOneByAttribute(string $attribute, string $value, array $columns = ['*'])
    {
        return $this->model::where($attribute, '=', $value)->firstOrFail($columns);
    }

    public function getCollectionWhereIn(string $attribute, array $values, array $columns = ['*'])
    {
        return $this->model::whereIn($attribute, $values)->get($columns);
    }

    public function getCollectionWhereBetween(string $attribute, array $values, array $columns = ['*']): Collection
    {
        return $this->whereBetween($attribute, $values)->get($columns);
    }

    public function count(): int
    {
        return $this->model::count();
    }

    public function load(int|string $id, array $columns = ['*'])
    {
        return $this->model->find($id, $columns);
    }

    public function loadRelations(Model $model, array $relations): void
    {
        $model->load($relations);
    }

    protected function exception(string $message, int $code = 0): void
    {
        throw new \Exception($message, $code);
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
            ->orderBy($orderBy, strtoupper($orderDirection) == 'ASC' ? 'ASC' : 'DESC')
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
