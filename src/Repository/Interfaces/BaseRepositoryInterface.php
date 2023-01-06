<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function create(array $data = []): Model;

    public function getCollection(array $columns = ['*']): Collection;

    public function getCollectionByIds(array $ids, array $columns = ['*']): Collection;

    public function update(int $id, string $attribute = 'id', array $data = []): bool;

    public function updateMany(array $ids, array $data): int;

    public function delete($ids): int;

    public function findById(int $id, array $columns = ['*'], array $with = []): Model;

    public function findOneByAttribute(string $attribute, string $value, array $columns = ['*']): Model;

    public function getCollectionWhereIn(string $attribute, array $values, array $columns = ['*']): Collection;

    public function getCollectionWhereBetween(string $attribute, array $values, array $columns = ['*']): Collection;

    public function count(): int;

    public function load(int|string $id, array $columns = ['*']): Model;

    public function loadRelations(Model $model, array $relations): void;

    public function findByOrderBy(
        string $field,
        mixed $value,
        array $columns = ['*'],
        string $orderBy = 'id',
        string $orderDirection = 'DESC'
    ): ?Model;

    public function __call(string $name, array $arguments): mixed;
}
