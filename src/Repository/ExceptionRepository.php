<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Model;
use Throwable;
use xmlshop\QueueMonitor\Models\Exception;
use xmlshop\QueueMonitor\Repository\Interfaces\ExceptionRepositoryInterface;

class ExceptionRepository implements ExceptionRepositoryInterface
{
    public function __construct(protected Exception $model)
    {
    }

    public function createFromThrowable(Throwable $throwable): Model
    {
        $exceptionMaxLength = config('monitor.db.max_length_exception');
        $exceptionMessageMaxLength = config('monitor.db.max_length_exception_message');

        return $this->model->newModelQuery()->create([
            'entity' => Exception::ENTITY_JOB,
            'exception' => mb_strcut((string) $throwable, 0, $exceptionMaxLength),
            'exception_class' => get_class($throwable),
            'exception_message' => mb_strcut($throwable->getMessage(), 0, $exceptionMessageMaxLength),
            'created_at' => now()
        ]);
    }
}
