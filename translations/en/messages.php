<?php

return [
    'errors' => [
        'access_denied' => 'Access denied.',
        'not_found' => 'Not found.',
        'expired' => 'The file is no longer available.',
        'upload_failed' => 'Upload has failed.',
        'upload_denied' => 'Upload has been denied.',
        'delete_denied' => 'Resource deletion has been denied.',
        'delete_failed' => 'Resource deletion has failed',
    ],
    'console' => [
        'done' => 'Done!',
        'cleanup_description' => 'Cleanup attachments not bound to a model instance.',
        'cleanup_confirm' => 'Do you confirm the deletion of unbound attachments ?',
        'cleanup_option_since' => 'Minimum age (in minutes) of the attachment to delete (based on modification date).',
        'cleanup_no_data' => 'No attachment to handle.',
        'migrate_description' => 'Migrate attachments from given disk to another keeping path.',
        'migrate_option_from' => 'Source disk.',
        'migrate_option_to' => 'Destination disk.',
        'migrate_error_missing' => 'Cannot not migrate to the same disk.',
        'migrate_error_from' => 'Unknown source disk.',
        'migrate_error_to' => 'Unknown target disk.',
        'migrate_invalid_from' => 'Unreadable source disk.',
        'migrate_invalid_to' => 'Unreadable destination disk.',
    ],
];
