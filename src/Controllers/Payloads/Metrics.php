<?php

namespace xmlshop\QueueMonitor\Controllers\Payloads;

final class Metrics
{
    /**
     * @var \xmlshop\QueueMonitor\Controllers\Payloads\Metric[]
     */
    public $metrics = [];

    /**
     * @return \xmlshop\QueueMonitor\Controllers\Payloads\Metric[]
     */
    public function all(): array
    {
        return $this->metrics;
    }

    public function push(Metric $metric): self
    {
        $this->metrics[] = $metric;

        return $this;
    }
}
