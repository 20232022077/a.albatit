<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | How many completed backups to keep. Every time a new backup finishes
    | successfully, older ones beyond this count are pruned automatically
    | (oldest first). Manual deletion from the admin panel is always
    | available on top of this.
    |
    */

    'keep_count' => (int) env('BACKUP_KEEP_COUNT', 10),

    /*
    |--------------------------------------------------------------------------
    | mysqldump / mysql binaries
    |--------------------------------------------------------------------------
    |
    | Full paths to the command-line clients used to dump and restore the
    | database. Left blank, the service falls back to "mysqldump"/"mysql" on
    | the system PATH.
    |
    */

    'mysqldump_path' => env('BACKUP_MYSQLDUMP_PATH'),

    'mysql_path' => env('BACKUP_MYSQL_PATH'),

    /*
    |--------------------------------------------------------------------------
    | Files included in every backup
    |--------------------------------------------------------------------------
    |
    | Storage-relative directories archived alongside the database dump:
    | uploaded media (public disk) and privately-stored files such as PDFs
    | (local disk). Never includes the backups directory itself.
    |
    */

    'include_paths' => [
        'storage-public' => storage_path('app/public'),
        'storage-private' => storage_path('app/private'),
    ],

];
