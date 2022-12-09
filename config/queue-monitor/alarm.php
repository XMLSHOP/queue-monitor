<?php

declare(strict_types=1);

return [
    'is_active' => true,

    'channel' => 'slack',

    'time_between_alerts' => 5 * 60, // seconds

    'recipient' => '#notifications',

    'routes' => [
        'jobs' => 'https://{domain}}/queue-monitor/jobs',
        'queue-sizes' => 'https://{domain}}/queue-monitor/queue-sizes',
    ],

    'jobs_compare_alerts' => [
        'last' => 5 * 60, // seconds
        'previous' => 60 * 60, // seconds
    ],

    // per jobs_compare_alerts.last
    'jobs_thresholds' => [
        'failing_count' => 5,
        'pending_count' => 10,
        'pending_time' => 120, // seconds

        'pending_time_to_previous' => true,
        'execution_time_to_previous' => true,

        'pending_time_to_previous_factor' => 1.5,
        'execution_time_to_previous_factor' => 1.5,

        'exceptions' => [
            /*
            'MonitoredFailingJob' => [
                'ignore' => true,
            ],
            'MonitoredJob' => [
                'pending_count' => 5,
            ],
            'MonitoredJobWithArguments' => [
                'pending_time' => 60, // seconds
            ],
            'MonitoredJobWithData' => [
                'execution_time_to_previous' => 1.1, // seconds
            ],
            */
        ],
    ],
];
