<?php

declare(strict_types=1);

return [
    'root' => [
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
        /*
        [
            'properties' => [
                'GChartOptions' => [
                    'height' => 100,
                ],
                'ref' => 'SecondQueue',
                'code' => 'SecondQueue',
                'type' => 'queues',
                'title' => 'Second queue',
            ],
            'queues' => [
                'sqs-second'
            ],
        ],
        */
    ],
];
