# Laravel 5.4+ file attachment helpers

This package allows to quickly links files to models.


## Installation

Add the service provider to your configuration :

```php
'providers' => [
        // ...

        Bnb\Laravel\Attachments\AttachmentsServiceProvider::class,

        // ...
],
```


## Configuration

You can customize this package behavior by publishing the configuration file :

    php artisan vendor:publish --provider='Bnb\Laravel\Attachments\AttachmentsServiceProvider'


## Add attachments to a model class

Add the `HasAttachment` trait to your model class :

```php
<?php

namespace App;

use Bnb\Laravel\Attachments\HasAttachment;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable, HasAttachment;

    // ...
}
```

Then use it to bind a file to an instance :

```php
$user = App\User::create([
    'name' => 'name',
    'email' => 'email@foo.bar',
    'password' => 'password',
]);

// Bind a local file
$attachment = $user->attach('local/path/to/file.txt');

// Bind an uploaded file
$attachment = $user->attach(\Request::file('uploaded_file'));

// Bind an uploaded file with options
$attachment = $user->attach(\Request::file('uploaded_file'), [
    'disk' => 's3',
    'title' => \Request::input('attachment_title'),
    'description' => \Request::input('attachment_description'),
    'key' => \Request::input('attachment_key'),
]);
```

## Retrieve model's attachments


```php
$user = App\User::first();

$allAttachments = $user->attachments()->get();

$attachmentByKey = $user->attachment('myKey');

// Attachment public URL
$publicUrl = $attachmentByKey->url;
```

## Hooking the file output

The `Bnb\Laravel\Attachments\Attachment` model class provides
 an `outputting` event that you can observe.

In the application service provider you could write for example :

```php
<?php
use Bnb\Laravel\Attachments\Attachment;

class AppServiceProvider extends ServiceProvider
{
    // ...

    public function boot() {

        // ...

        Attachment::outputting(function ($attachment) {
            /** @var Attachment $attachment */

            // Get the related model
            $model = $attachment->model;

            if (empty($model)) {
                // Deny output for attachments not linked to a model

                return false;
            }

            if ($model instanceof \App\User) {
                // Check if current user is granted and owner

                $user = \Auth::user();

                return $user && $user->can('download-file') && $user->id == $model->id;
            }
        });

        // ...
    }


    // ...
}
```