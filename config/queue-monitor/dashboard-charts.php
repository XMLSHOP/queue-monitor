<?php

declare(strict_types=1);

return [
    [
        'properties' => [
            'GChartOptions' => [
                'height' => 200,
            ],
            'ref' => 'OtherQueues',
            'code' => 'OtherQueues',
            'type' => 'queues',
            'title' => 'Other queues',
        ],
        'queues' => [
            'database-default',
        ],
    ],
    [
        'properties' => [
            'GChartOptions' => [
                'height' => 100,
            ],
            'ref' => 'TrackingQueue',
            'code' => 'TrackingQueue',
            'type' => 'queues',
            'title' => 'Tracking queue',
        ],
        'queues' => [
            'sqs-tracking'
        ],
    ],
];
