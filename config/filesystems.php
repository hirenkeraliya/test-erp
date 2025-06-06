<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'ioi_city_mall' => [
            'driver' => 'local',
            'root' => storage_path('app/ioi_city_mall'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        'oci' => [
            'driver' => 's3',
            'key' => env('OCI_ACCESS_KEY_ID'),
            'secret' => env('OCI_SECRET_ACCESS_KEY'),
            'region' => env('OCI_DEFAULT_REGION'),
            'bucket' => env('OCI_BUCKET'),
            'url' => env('OCI_URL'),
            'endpoint' => "https://" . env('NAMESPACE_OF_BUCKET') . ".compat.objectstorage." . env('OCI_DEFAULT_REGION') . ".oraclecloud.com",
            'use_path_style_endpoint' => true, // This must be true for OCI to work
            'throw' => false,
        ],

        'e_invoice_summary_ftp' => [
            'driver'   => 'ftp',
            'host'     => env('SFTP_IP_ADDRESS'),
            'username' => env('SFTP_USERNAME'),
            'password' => env('SFTP_PASSWORD'),

            // Optional FTP Settings
            'port'     => (int) env('SFTP_PORT'),
            'root'     => env('SFTP_PATH'), // root folder on the FTP server
            'passive'  => true,
            'ssl'      => false,
            'timeout'  => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
