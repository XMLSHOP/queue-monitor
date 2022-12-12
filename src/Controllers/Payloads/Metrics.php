<?php

declare(strict_types=1);

namespace xmlshop\QueueMonitor\Controllers\Payloads;

final class Metrics
{
    public $metrics = [];

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
