<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Throwable;
use xmlshop\QueueMonitor\Models\Command;
use xmlshop\QueueMonitor\Models\Host;
use xmlshop\QueueMonitor\Models\MonitorCommand;
use xmlshop\QueueMonitor\Repository\Interfaces\CommandRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\ExceptionRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\HostRepositoryInterface;
use xmlshop\QueueMonitor\Services\System\SystemResourceInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\MonitorCommandRepositoryInterface;

class MonitorCommandRepository implements MonitorCommandRepositoryInterface
{
    public function __construct(
        private MonitorCommand $model,
        private SystemResourceInterface $systemResources,
        private CommandRepositoryInterface $commandRepository,
        private HostRepositoryInterface $hostRepository,
        private ExceptionRepositoryInterface $exceptionRepository
    ) {
    }

    public function createByCommandAndHost(Command $command, Host $host): void
    {
        $this->model
            ->newQuery()
            ->create([
                'command_id' => $command->id,
                'host_id' => $host->id,
                'started_at' => now(),
                'time_elapsed' => 0,
                'failed' => false,
                'pid' => $this->systemResources->getProcessId(),
                'use_memory_mb' => $this->systemResources->getMemoryUseMb(),
                'use_cpu' => $this->systemResources->getCpuUse(),
                'created_at' => now(),
            ]);
    }

    public function updateByCommandAndHost(Command $command, Host $host): void
    {
        /** @var MonitorCommand $monitorCommand */
        $monitorCommand = $this->model
            ->newQuery()
            ->where([
                'command_id' => $command->id,
                'host_id' => $host->id,
            ])
            ->whereNull('finished_at')
            ->orderByDesc('started_at')
            ->first();

        $monitorCommand && $monitorCommand->update([
            'finished_at' => now(),
            'time_elapsed' => $this->systemResources->getTimeElapsed($monitorCommand->started_at, now()),
            'use_memory_mb' => max([$monitorCommand->use_memory_mb, $this->systemResources->getMemoryUseMb()]),
            'use_cpu' => max([$monitorCommand->use_cpu, $this->systemResources->getCpuUse()]),
        ]);
    }

    public function getList(Request $request, $filters)
    {
        return $this->model
            ->newQuery()
            ->where(function ($query) use ($filters) {
                $query
                    ->orWhereBetween('started_at', [$filters['df'], $filters['dt']])
                    ->orWhereBetween('finished_at', [$filters['df'], $filters['dt']]);
            })
            ->with(['command', 'host'])
            ->when(($type = $filters['type']) && 'all' !== $type, static function (Builder $builder) use ($type) {
                switch ($type) {
                    case 'running':
                        /** @noinspection UnknownColumnInspection */
                        $builder->whereNotNull('started_at')->whereNull('finished_at');
                        break;

                    case 'failed':
                        /** @noinspection UnknownColumnInspection */
                        $builder->where('failed', true)->whereNotNull('finished_at');
                        break;

                    case 'succeeded':
                        /** @noinspection UnknownColumnInspection */
                        $builder->where('failed', false)->whereNotNull('finished_at');
                        break;
                }
            })
            ->when(($command_id = $filters['command']) && 'all' !== $command_id, static function (Builder $builder) use ($command_id) {
                /** @noinspection UnknownColumnInspection */
                $builder->where('command_id', $command_id);
            })
            ->orderByDesc('started_at')
            ->orderByDesc('finished_at')
            ->paginate(
                config('monitor.ui.per_page')
            )
            ->appends(
                $request->all()
            );
    }

    public function getListRunning(): Collection
    {
        return $this->model
            ->newQuery()
            ->select(['uuid', 'started_at', 'pid'])
            ->whereRelation('host', 'name', $this->systemResources->getHost())
            ->whereNotNull('started_at')
            ->whereNull('finished_at')
            ->get();
    }

    public function updateFailedExternally(string $command_name, Throwable $exception): void
    {
        $command = $this->commandRepository->findByName($command_name);
        $host = $this->hostRepository->firstOrCreate();
        $exceptionModel = $this->exceptionRepository->createFromThrowable($exception);

        /** @var MonitorCommand $monitorCommand */
        $monitorCommand = $this->model
            ->newQuery()
            ->where([
                'command_id' => $command->id,
                'host_id' => $host->id,
            ])
            ->orderByDesc('started_at')
            ->first();

        $monitorCommand && $monitorCommand->update([
            'exception_id' => $exceptionModel->uuid,
            'finished_at' => now(),
            'failed' => true,
            'pid' => $this->systemResources->getProcessId(),
            'time_elapsed' => $this->systemResources->getTimeElapsed($monitorCommand->started_at, now()),
            'use_memory_mb' => $this->systemResources->getMemoryUseMb(),
            'use_cpu' => $this->systemResources->getCpuUse(),
        ]);
    }

    public function purge(int $days): void
    {
        $this->model
            ->newQuery()
            ->where('started_at', '<=', Carbon::now()->subDays($days))
            ->delete();
    }
}
