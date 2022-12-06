<?php

declare(strict_types=1);

return [
    /*
     * Set the table to be used for monitoring data.
     */
    'connection' => null,

    'table' => [
        'jobs' => 'x_jobs',
        'queues' => 'x_queues',
        'queues_sizes' => 'x_queues_sizes',
        'hosts' => 'x_hosts',
        'monitor_queue' => 'x_monitor_queue',
        'monitor_scheduler' => 'x_monitor_scheduler',
    ],

    /*
     * Specify the max character length to use for storing exception backtraces.
     */
    'max_length_exception' => 4294967295,
    'max_length_exception_message' => 65535,

    /*
     * Purge monitor & queue_sizes tables after days
     */
    'clean_after_days' => 14,
];
