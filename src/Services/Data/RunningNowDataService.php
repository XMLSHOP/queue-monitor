<?php

namespace xmlshop\QueueMonitor\Services\Data;

use Illuminate\Support\Collection;

class RunningNowDataService
{
    /**
     * @param array<int, array<string, mixed>> $hosts
     * @param array<string, iterable<object>> $summaries Running monitors keyed by group (schedulers, commands, jobs),
     *                                                   one row per host and name: host_id, name, amount, cpu, memory
     * @return Collection<int, array<string, mixed>>
     */
    public function beautifierHostSummary(array $hosts, array $summaries): Collection
    {
        $data = [];

        foreach ($hosts as $host) {
            $data[$host['id']] = $host
                + array_fill_keys(array_keys($summaries), [])
                + ['total' => ['cpu' => 0, 'memory' => 0]];
        }

        foreach ($summaries as $group => $rows) {
            foreach ($rows as $row) {
                if (!isset($data[$row->host_id])) {
                    continue;
                }

                $usage = ['amount' => (int)$row->amount, 'cpu' => (float)$row->cpu, 'memory' => (int)$row->memory];

                $data[$row->host_id][$group][$row->name] = $usage;
                $data[$row->host_id]['total']['cpu'] += $usage['cpu'];
                $data[$row->host_id]['total']['memory'] += $usage['memory'];
            }
        }

        return collect(array_values($data));
    }
}
