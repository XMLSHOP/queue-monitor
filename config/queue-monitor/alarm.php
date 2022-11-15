<?php

declare(strict_types=1);

return [
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
        'pending_time' => 60, // seconds

        'pending_time_to_previous' => 1.2,
        'execution_time_to_previous' => 1.2,
    ],

    'jobs_meta' => [
        'MonitoredFailingJob' => [
            'ignore_failing' => true,
        ],
        'MonitoredJob' => [
            'threshold_pending_count' => 15,
        ],
        'MonitoredJobWithArguments' => [
            'threshold_pending_time' => 60, // seconds
        ],
        'MonitoredJobWithData' => [
            'threshold_execution_time' => 0.5, // seconds
        ],
    ],
];
