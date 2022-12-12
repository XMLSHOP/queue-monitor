<?php

declare(strict_types=1);

//The optional UI settings.
return [

    //Whether we need to display Progress.
    'show_progress_column' => false,

    //Set the monitored jobs count to be displayed per page.
    'per_page' => 35,

    //Show custom data stored on model
    'show_custom_data' => true,

    //Allow the deletion of single monitor items.
    'allow_deletion' => true,

    //Allow purging all monitor entries.
    'allow_purge' => false,

    'show_metrics' => false,

    'show_summary' => true,
    'summary_conf' => [
        'failed',
        'succeeded',
        'pending',
        'running',
    ],

    //Time frame used to calculate metrics values (in days).
    'metrics_time_frame' => 14,
];
