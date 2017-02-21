<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Determine whether or not to automatically define attachments routes.
    | Used for local storage only as other storage should define their public URL.
    |
    */
    'routes' => [
        'publish' => true,
        'prefix' => 'attachments',
        'middleware' => 'web',
        'pattern' => '/{id}/{name}',
    ],

    /*
    |--------------------------------------------------------------------------
    | Uuid
    |--------------------------------------------------------------------------
    |
    | Attachment model uses an UUID column. You can define your own UUID
    | generator here : a global function name or a static class method in the form :
    | App\Namespace\ClassName@method
    |
    */
    'uuid_provider' => 'uniqid',

    /*
    |--------------------------------------------------------------------------
    | Behaviors
    |--------------------------------------------------------------------------
    |
    | Configurable behaviors :
    | - Concrete files can be delete when the database entry is deleted
    |
    */
    'behaviors' => [
        'cascade_delete' => true,
    ]
];