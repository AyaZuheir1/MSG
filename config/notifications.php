<?php

return [
    'default' => env('BROADCAST_DRIVER', 'fcm'),

    'channels' => [
        'fcm' => [
            'key' => env('FCM_SERVER_KEY'),
        ],
    ],
];
?>