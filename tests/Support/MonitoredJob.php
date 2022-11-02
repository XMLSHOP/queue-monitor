<?php

namespace xmlshop\QueueMonitor\Tests\Support;

use xmlshop\QueueMonitor\Traits\IsMonitored;

class MonitoredJob extends BaseJob
{
    use IsMonitored;
}
