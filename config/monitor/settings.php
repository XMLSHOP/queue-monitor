<?php

declare(strict_types=1);

return [
    'active' => true,

    'active-monitor-queue-sizes' => true,
    'active-monitor-queue-jobs' => true,
    'active-monitor-scheduler' => true,
    'active-monitor-commands' => true,

    'active-alarm' => true,

    'jobsToSkipQueued' => [],

    'commandsToSkipMonitor' => [
        null, // Appears when `php artisan` had been launched without args
        'migrate:fresh',
        'migrate:rollback',
        'migrate',
        'queue:table',
        'queue:work',
        'schedule:work',
        'scheduler:work',
        'schedule:run',
        'vendor:publish',
        'package:discover',
        'help',
    ],
];
