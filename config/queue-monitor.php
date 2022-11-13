<?php

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
    ],

    /*
     * Specify the max character length to use for storing exception backtraces.
     */
    'db_max_length_exception' => 4294967295,
    'db_max_length_exception_message' => 65535,

    /*
     * The optional UI settings.
     */
    'ui' => [
        /*
         * Set the monitored jobs count to be displayed per page.
         */
        'per_page' => 35,

        /*
         *  Show custom data stored on model
         */
        'show_custom_data' => false,

        /**
         * Allow the deletion of single monitor items.
         */
        'allow_deletion' => true,

        /**
         * Allow purging all monitor entries.
         */
        'allow_purge' => true,

        'show_metrics' => true,

        'show_summary' => true,
        'summary_conf' => [
            'failed',
            'succeeded',
            'pending',
            'running',
        ],

        /**
         * Time frame used to calculate metrics values (in days).
         */
        'metrics_time_frame' => 14,
    ],

    // might be table or config
    'queues_sizes_retrieves_mode' => 'table',
//    'queues_sizes_retrieves_mode' => 'config',
    'queues_sizes_retrieves_config' => [
        'envs' => [
            'default' => [
                [
                    'queue_name' => 'default',
                    'connection_name' => 'sqs',
                ],
                [
                    'queue_name' => 'parcels',
                    'connection_name' => 'sqs',
                ],
                [
                    'queue_name' => 'tracking',
                    'connection_name' => 'sqs',
                ],
                [
                    'queue_name' => 'default',
                    'connection_name' => 'database',
                ],
                [
                    'queue_name' => 'batches',
                    'connection_name' => 'database',
                ],
                [
                    'queue_name' => 'service-report',
                    'connection_name' => 'database',
                ],
            ],
        ],
    ],
];
