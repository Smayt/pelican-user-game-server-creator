<?php
return [
    'database_limit'   => env('UGSC_DATABASE_LIMIT', 0),
    'allocation_limit' => env('UGSC_ALLOCATION_LIMIT', 1),
    'backup_limit'     => env('UGSC_BACKUP_LIMIT', 1),
    'deployment_tags'  => env('UGSC_DEPLOYMENT_TAGS', 'user_creatable_servers'),
];
