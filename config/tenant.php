<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domain & database prefix
    |--------------------------------------------------------------------------
    | Live: {tenant}.quanlytrungtam.com → {prefix}{tenant}
    | Ví dụ: tpt-academy.quanlytrungtam.com → crmtt_tpt-academy
    */
    'base_domain' => env('TENANT_BASE_DOMAIN', 'quanlytrungtam.com'),
    'database_prefix' => env('TENANT_DATABASE_PREFIX', 'crmtt_'),

    /*
    |--------------------------------------------------------------------------
    | Tenant resolution (subdomain → DB)
    |--------------------------------------------------------------------------
    | Local/Docker: TENANT_RESOLVE=false — dùng DB_DATABASE trong .env
    | Live:         TENANT_RESOLVE=true  — mỗi subdomain một database
    */
    'tenant_resolve' => filter_var(env('TENANT_RESOLVE', false), FILTER_VALIDATE_BOOLEAN),

];
