<?php

namespace xmlshop\QueueMonitor\Services\Data;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class RunningNowDataService
{

    public function beautifierHostSummary(array $data): Collection
    {
        foreach ($data as $key => $host) {
            $schedulers = [];
            $commands = [];
            $jobs = [];

            $total_host = [
                'cpu' => 0,
                'memory' => 0,
            ];

            foreach ($host['monitor_command'] as $command) {
                if (!Arr::exists($commands, $command['command']['command'])) {
                    $commands[$command['command']['command']] = ['amount' => 0, 'cpu' => 0, 'memory' => 0,];
                }

                $commands[$command['command']['command']]['amount']++;
                $commands[$command['command']['command']]['cpu'] += $command['use_cpu'];
                $commands[$command['command']['command']]['memory'] += $command['use_memory_mb'];

                $total_host['cpu'] += $command['use_cpu'];
                $total_host['memory'] += $command['use_memory_mb'];
            }

            foreach ($host['monitor_scheduler'] as $scheduler) {
                if (!Arr::exists($schedulers, $scheduler['scheduler']['name'])) {
                    $schedulers[$scheduler['scheduler']['name']] = ['amount' => 0, 'cpu' => 0, 'memory' => 0,];
                }

                $schedulers[$scheduler['scheduler']['name']]['amount']++;
                $schedulers[$scheduler['scheduler']['name']]['cpu'] += $scheduler['use_cpu'];
                $schedulers[$scheduler['scheduler']['name']]['memory'] += $scheduler['use_memory_mb'];

                $total_host['cpu'] += $scheduler['use_cpu'];
                $total_host['memory'] += $scheduler['use_memory_mb'];
            }

            foreach ($host['monitor_queue'] as $job) {

                if (!Arr::exists($jobs, $job['job']['name'])) {
                    $jobs[$job['job']['name']] = ['amount' => 0, 'cpu' => 0, 'memory' => 0,];
                }

                $jobs[$job['job']['name']]['amount']++;
                $jobs[$job['job']['name']]['cpu'] += $job['use_cpu'];
                $jobs[$job['job']['name']]['memory'] += $job['use_memory_mb'];

                $total_host['cpu'] += $job['use_cpu'];
                $total_host['memory'] += $job['use_memory_mb'];
            }

            $data[$key]['schedulers'] = $schedulers;
            $data[$key]['commands'] = $commands;
            $data[$key]['jobs'] = $jobs;
            unset($data[$key]['monitor_scheduler'], $data[$key]['monitor_command'], $data[$key]['monitor_queue']);
            $data[$key]['total'] = $total_host;
        }

        return collect($data);
    }
}