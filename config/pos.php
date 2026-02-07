<?php

return [
    'device_enforce' => env('POS_DEVICE_ENFORCE', false),
    'device_cookie' => env('POS_DEVICE_COOKIE', 'pos_device_token'),
    'pairing_ttl_minutes' => env('POS_DEVICE_PAIRING_TTL', 15),
    'token_ttl_days' => env('POS_DEVICE_TOKEN_TTL_DAYS', 365),
];
