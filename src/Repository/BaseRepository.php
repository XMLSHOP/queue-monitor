<?php

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository
{
    /**
     * @var Model
     */
    protected Model $model;

    /**
     * Retrieve model class name
     *
     * @return string
     */
    abstract public function getModelName(): string;

    /**
     * BaseRepository constructor.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct()
    {
        $this->makeModel();
    }

    /**
     * @param Model $model
     * @return bool
     */
    public function save(Model $model): bool
    {
        return $model->save();
    }

    /** @inheritdoc */
    public function makeModel(): void
    {
        $model = app($this->getModelName());

        if (!$model instanceof Model) {
            throw new \Exception("Class $model must be an instance of Illuminate\Database\Eloquent\Model");
        }

        $this->model = $model;
    }

    /** @inheritdoc */
    public function create(array $data = []): Model
    {
        return $this->model->create($data);
    }

    /** @inheritdoc */
    public function getFillableFields(): array
    {
        $fillable = $this->model->getFillable();
        if (\is_callable([$this->model, 'getNotFillable'])) {
            $fillable = array_diff($fillable, $this->model->getNotFillable());
        }
        return $fillable;
    }

    /** @inheritdoc */
    public function getCollection(array $columns = ['*'])
    {
        return $this->model::get($columns);
    }

    /** @inheritdoc */
    public function getCollectionByIds(array $ids, array $columns = ['*'])
    {
        return $this->model::findMany($ids, $columns);
    }

    /** @inheritdoc */
    public function update(int $id, string $attribute = 'id', array $data = []): bool
    {
        $model = $this->model::where($attribute, '=', $id)->first();

        if (!$model) {
            return false;
        }

        $model->fill($data);

        return $model->save();
    }

    /** @inheritdoc */
    public function updateMany(array $ids, array $data): int
    {
        return $this->model::whereIn('id', $ids)->update($data);
    }

    /** @inheritdoc */
    public function delete($ids): int
    {
        return $this->model::destroy($ids);
    }

    /** @inheritdoc */
    public function findById(int $id, array $columns = ['*'], array $with = [])
    {
        return $this->model::with($with)->findOrFail($id, $columns);
    }

    /** @inheritdoc */
    public function findOneByAttribute(string $attribute, string $value, array $columns = ['*'])
    {
        return $this->model::where($attribute, '=', $value)->firstOrFail($columns);
    }

    /** @inheritdoc */
    public function getCollectionWhereIn(string $attribute, array $values, array $columns = ['*'])
    {
        return $this->model::whereIn($attribute, $values)->get($columns);
    }

    /** @inheritdoc */
    public function getCollectionWhereBetween(string $attribute, array $values, array $columns = ['*']): Collection
    {
        return $this->whereBetween($attribute, $values)->get($columns);
    }



    /** @inheritdoc */
    public function count(): int
    {
        return $this->model::count();
    }

    /**
     * @param $id
     * @param string[] $columns
     * @return Collection|Model|Model[]|mixed|null
     */
    public function load($id, $columns = ['*'])
    {
        return $this->model->find($id, $columns);
    }

    /**
     * @param Model $model
     * @param array $relations
     */
    public function loadRelations(Model $model, array $relations): void
    {
        $model->load($relations);
    }

    /**
     * Throw repository internal exception
     *
     * @param string $message
     * @param int    $code
     *
     * @throws \Exception
     */
    protected function exception(string $message, int $code = 0): void
    {
        throw new \Exception($message, $code);
    }

    /**
     * Find records by attribute.
     *
     * @param  string $name
     * @param  array  $arguments
     *
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return \call_user_func_array([$this->model, $name], $arguments);
    }
}