<?php

namespace xmlshop\QueueMonitor\Tests;

use xmlshop\QueueMonitor\Services\ClassUses;
use xmlshop\QueueMonitor\Tests\Support\MonitoredExtendingJob;
use xmlshop\QueueMonitor\Tests\Support\MonitoredJob;
use xmlshop\QueueMonitor\Traits\IsMonitored;

class ClassUsesTraitTest extends TestCase
{
    public function testUsingMonitorTrait()
    {
        $this->assertArrayHasKey(
            IsMonitored::class,
            ClassUses::classUsesRecursive(MonitoredJob::class)
        );
    }

    public function testUsingMonitorTraitExtended()
    {
        $this->assertArrayHasKey(
            IsMonitored::class,
            ClassUses::classUsesRecursive(MonitoredExtendingJob::class)
        );
    }
}
