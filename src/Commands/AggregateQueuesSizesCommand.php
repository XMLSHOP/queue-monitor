<?php

namespace xmlshop\QueueMonitor\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use xmlshop\QueueMonitor\Repository\QueueMonitorQueueRepository;
use xmlshop\QueueMonitor\Repository\QueueMonitorQueueSizesRepository;

class AggregateQueuesSizesCommand extends Command
{
    /**
     * Command retrieves information about queues sizes and storing that in table
     *
     * @var string
     */
    protected $signature = 'queue-monitor:aggregate-queues-sizes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command gets sizes of declared queues and store them to the table.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private QueueMonitorQueueSizesRepository $queuesSizeRepository,
        private QueueMonitorQueueRepository $queueRepository
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     */
    public function handle()
    {
        $queuesIds = $this->getQueuesIds();

        $timestamp = Carbon::now()->toDateTimeLocalString();
        $data = [];
        foreach ($queuesIds as $value) {
            $size = Queue::connection($value['connection'])->size($value['queue']);
            $data[] = [
                'queue_id' => $value['id'],
                'size' => $size,
                'created_at' => $timestamp,
            ];
        }
        $this->queuesSizeRepository->bulkInsert($data);

        Artisan::command('queue-monitor:listener');
        return 0;
    }

    /**
     * @param string|null $mode
     * @return mixed
     * @throws \Exception
     */
    private function getQueuesIds(?string $mode = null)
    {
        return match ($mode ?? config('queue-monitor.queue-sizes-retrieves.mode')) {
            'db' => call_user_func(function () {
                $out = [];
                foreach (collect($this->queueRepository->select())->toArray() as $value) {
                    $out[$value['connection_name'] . ':' . $value['queue_name']] = [
                        'queue' => $value['queue_name'],
                        'connection' => $value['connection_name'],
                        'id' => $value['id'],
                    ];
                }
                return $out;
            }),
            'config' => call_user_func(function () {
                $queues = config('queue-monitor.queue-sizes-retrieves.config.envs.' . App::environment());
                if (empty($queues)) {
                    $queues = config('queue-monitor.queue-sizes-retrieves.config.envs.default');
                }
                $out = $this->getQueuesIds('db');

                foreach ($queues as $value) {
                    if (!array_key_exists($value['connection_name'] . ':' . $value['queue_name'], $out)) {
                        $model = $this->queueRepository->addNew($value['connection_name'], $value['queue_name']);
                        $out[$model->connection_name . ':' . $model->queue_name] = [
                            'queue' => $value['queue_name'],
                            'connection' => $value['connection_name'],
                            'id' => $model->id,
                        ];
                    }
                }

                return $out;
            }),
            default => throw new \Exception('Wrong [queue-sizes-retrieves.mode]!')
        };
    }

}
