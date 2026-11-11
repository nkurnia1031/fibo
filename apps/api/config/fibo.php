<?php

return [
    'owner' => [
        'name' => env('FIBO_OWNER_NAME'),
        'email' => env('FIBO_OWNER_EMAIL'),
        'password' => env('FIBO_OWNER_PASSWORD'),
    ],
    'reviewer' => [
        'name' => env('FIBO_REVIEWER_NAME'),
        'email' => env('FIBO_REVIEWER_EMAIL'),
        'password' => env('FIBO_REVIEWER_PASSWORD'),
    ],
];
