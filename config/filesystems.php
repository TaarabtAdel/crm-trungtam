<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Shared hosting thường không cho symlink storage:link.
    | Disk "public" trỏ thẳng vào public/storage (không cần link).
    | Mỗi tenant dùng thư mục con public/storage/{subdomain}/ (set runtime).
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            // Mặc định; middleware ResolveTenantDatabase sẽ đổi sang public/storage/{subdomain}
            'root' => public_path('storage/local'),
            'url' => rtrim(env('APP_URL', ''), '/').'/storage/local',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Không dùng trên shared hosting. Giữ cấu hình trống / không bắt buộc chạy.
    |
    */

    'links' => [
        // public_path('storage') => storage_path('app/public'),
    ],

];
