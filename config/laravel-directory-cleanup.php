<?php

use App\Policy\LogExtensionFilesCleanup;

return [

    'directories' => [

        /*
         * Here you can specify which directories need to be cleanup. All files older than
         * the specified amount of minutes will be deleted.
         */

        'storage/app/barcode_print/' => [
            'deleteAllOlderThanMinutes' => 60 * (24 * 2),
        ],
        'storage/app/public/temporary_files/' => [
            'deleteAllOlderThanMinutes' => 60 * (24 * 2),
        ],
        'storage/app/excel_exports/' => [
            'deleteAllOlderThanMinutes' => 60 * (24 * 3),
        ],
        'storage/logs/' => [
            'deleteAllOlderThanMinutes' => 525600, // 1 year in minute
        ],
    ],

    /*
     * If a file is older than the amount of minutes specified, a cleanup policy will decide if that file
     * should be deleted. By default every file that is older than the specified amount of minutes
     * will be deleted.
     *
     * You can customize this behaviour by writing your own clean up policy. A valid policy
     * is any class that implements `Spatie\DirectoryCleanup\Policies\CleanupPolicy`.
     */
    'cleanup_policy' => LogExtensionFilesCleanup::class,
];
