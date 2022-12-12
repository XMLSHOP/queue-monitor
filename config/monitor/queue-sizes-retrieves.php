<?php

declare(strict_types=1);

return [
    // might be 'config' or 'db'
    'mode' => 'db',

    'config' => [

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
