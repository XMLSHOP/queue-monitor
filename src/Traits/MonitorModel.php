<?php

namespace xmlshop\QueueMonitor\Traits;

use Carbon\CarbonInterval;
use Illuminate\Support\Carbon;

trait MonitorModel
{
    public function isFinished(): bool
    {
        if ($this->hasFailed()) {
            return true;
        }

        return null !== $this->finished_at;
    }

    public function hasFailed(): bool
    {
        return true === $this->failed;
    }

    public function hasSucceeded(): bool
    {
        if (!$this->isFinished()) {
            return false;
        }

        return !$this->hasFailed();
    }


    public function getStarted(): ?Carbon
    {
        if (null === $this->started_at) {
            return null;
        }

        return Carbon::parse($this->started_at);
    }

    public function getFinished(): ?Carbon
    {
        if (null === $this->finished_at) {
            return null;
        }

        return Carbon::parse($this->finished_at);
    }
    /**
     * Get the currently elapsed seconds.
     */
    public function getElapsedSeconds(Carbon $end = null): float
    {
        return $this->getElapsedInterval($end)->seconds;
    }

    public function getElapsedInterval(Carbon $end = null): CarbonInterval
    {
        if (null === $end) {
            $end = $this->getFinished() ?? $this->finished_at ?? Carbon::now();
        }

        $startedAt = $this->getStarted() ?? $this->started_at;

        if (null === $startedAt) {
            return CarbonInterval::seconds(0);
        }

        return $startedAt->diffAsCarbonInterval($end);
    }

}