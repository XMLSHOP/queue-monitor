<?php

declare(strict_types=1);

return [
    'active' => true,

    //in use at AggregateQueuesSizesCommand
    'active-monitor-queue-sizes' => true,

    //in use at Providers
    'active-monitor-queue-jobs' => true,
    'active-monitor-scheduler' => true,
    'active-monitor-commands' => true,

    'active-alarm' => true,
];
