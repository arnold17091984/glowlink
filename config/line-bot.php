<?php

return [
    'channel_access_token' => env('LINE_BOT_CHANNEL_ACCESS_TOKEN'),
    'channel_id' => env('LINE_BOT_CHANNEL_ID'),
    'channel_secret' => env('LINE_BOT_CHANNEL_SECRET'),
    // LIFF クーポンウォレットで使用する LIFF ID
    'liff_id' => env('LIFF_ID'),
    'client' => [
        'config' => [
            'headers' => [],
        ],
    ],
];
