<?php

declare(strict_types=1);

return [

    //Set the table to be used for monitoring data.
    'connection' => null,

    'table' => [
        'hosts' => 'x_hosts',
        'exceptions' => 'x_exceptions_from_monitors',

        'jobs' => 'x_jobs',
        'queues_sizes' => 'x_queues_sizes',
        'queues' => 'x_queues',
        'monitor_queue' => 'x_monitor_queue',

        'commands' => 'x_commands',
        'monitor_command' => 'x_monitor_command',

        'schedulers' => 'x_schedulers',
        'monitor_scheduler' => 'x_monitor_scheduler',
    ],

    //Specify the max character length to use for storing exception backtraces.
    'max_length_exception' => 4294967295,
    'max_length_exception_message' => 65535,

    //Purge monitor & queue_sizes tables after days
    'clean_after_days' => 14,
];
