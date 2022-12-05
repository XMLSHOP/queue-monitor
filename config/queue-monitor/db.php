<?php

declare(strict_types=1);

return [
    /*
     * Set the table to be used for monitoring data.
     */
    'connection' => null,

    'table' => [
        'monitor' => 'x_queue_monitor',
        'monitor_jobs' => 'x_queue_monitor_jobs',
        'monitor_queues' => 'x_queue_monitor_queues',
        'monitor_queues_sizes' => 'x_queue_monitor_queues_sizes',
        'monitor_hosts' => 'x_queue_monitor_hosts',
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
