<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Commands;

use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use Webpatser\Uuid\Uuid;
use xmlshop\QueueMonitor\Repository\Interfaces\QueueRepositoryInterface;
use xmlshop\QueueMonitor\Repository\Interfaces\QueueSizeRepositoryInterface;
use function last;

class AggregateQueuesSizesCommand extends Command
{
    /**
     * Command retrieves information about queues sizes and storing that in table.
     *
     * @var string
     */
    protected $signature = 'monitor:aggregate-queues-sizes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command gets sizes of declared queues and store them to the table.';

    public function __construct(
        private QueueSizeRepositoryInterface $queuesSizeRepository,
        private QueueRepositoryInterface $queueRepository
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!config('monitor.settings.active') || !config('monitor.settings.active-monitor-queue-sizes')) {
            $this->error('Monitor is not active or Queue-Sizes monitor is not active.');
            return 0;
        }

        $timestamp = Carbon::now()->toDateTimeLocalString();
        $data = [];
        foreach ($this->getQueuesIds() as $value) {
            $size = Queue::connection($value['connection'])->size($value['queue']);
            $data[] = [
                'uuid' => Uuid::generate()->string,
                'queue_id' => $value['id'],
                'size' => $size,
                'created_at' => $timestamp,
            ];
        }
        $this->queuesSizeRepository->bulkInsert($data);

        return 0;
    }

    /**
     * @return array
     */
    private function getQueuesIds(): array
    {
        $out = [];
        foreach (collect($this->queueRepository->select())->toArray() as $value) {
            if (str_contains($value['queue_name'], '/')) {
                $value['queue_name'] = last(explode('\\', $value['queue_name']));
            }

            $out[$value['connection_name'] . ':' . $value['queue_name']] = [
                'queue' => $value['queue_name'],
                'connection' => $value['connection_name'],
                'id' => $value['id'],
            ];
        }

        return $out;
    }
}
