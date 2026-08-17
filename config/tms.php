<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Account provisioning
    |--------------------------------------------------------------------------
    |
    | Menkem TMS is an internal operational system. Public registration and
    | demo account provisioning are therefore opt-in, never production defaults.
    |
    */
    'public_registration' => (bool) env('TMS_PUBLIC_REGISTRATION', false),
    'seed_demo_users' => (bool) env('TMS_SEED_DEMO_USERS', false),
    'demo_user_password' => env('TMS_DEMO_USER_PASSWORD'),
];
