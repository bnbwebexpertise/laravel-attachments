# Laravel 5.x file attachment helpers

This package allows to quickly links files to models.

> Can also be installed on Laravel 5.4, [see below](#older-laravel-54-install)


## Installation

You can install this package via composer. Laravel 5.5+ auto discovers the service provider.

    composer require bnbwebexpertise/laravel-attachments
 

### Older Laravel 5.4 install

For 5.4 support install version 0.0.16 :

    composer require bnbwebexpertise/laravel-attachments:0.0.16

Then add the service provider to your configuration :

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

## Regroup model's attachments

The `group` attribute allows to group attachements.
The `attachementsGroup` method provided by the `HasAttachment` trait
 returns all the attachments with the given `group` value.

```php
$user = App\User::first();

$user->attach($somefile, ['
    'group' => 'album',
']);

$user->attach($otherfile, ['
    'group' => 'album',
']);

$attachmentsByGroup = $user->attachmentsGroup('album');
```

## Delete an attachment

Calling the `delete()` method on an attachment model instance will
 delete the database row and the file. The deletion of the file can
 be disabled by setting the `behaviors.cascade_delete` to `false` in
 the configuration.

> Not that calling `delete()` on a `query()` like statement will not
 cascade to the filesystem because it will not call the `delete()`
 method of the `Attachment` model class.

```php
$user = App\User::first();
$attachmentByKey = $user->attachment('myKey');
$attachmentByKey->delete(); // Will also delete the file on the storage by default
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

## Dropzone


### Upload

This package provides a server endpoint for [Dropzone.js](http://www.dropzonejs.com/) or equivalent
 via the `attachments.dropzone` route alias.

It returns the attachment `uuid` along other fields as a JSON response.
 This value can be sent back later to the server to bind it to a model
 instance (deferred saving).

The form :

```html
<form action="{{ route('attachments.dropzone')  }}" class="dropzone" id="my-dropzone">
    {{ csrf_field() }}
</form>
```

The response :

```json
{
  "title": "b39ffd84524b",
  "filename": "readme.md",
  "filesize": 2906,
  "filetype": "text\/html",
  "uuid": "f5a8eec2-d860-4e53-8451-b39ffd84524b",
  "key": "58ac52e90db938.72105394",
  "url": "http:\/\/laravel.dev:8888\/attachments\/f5a8eec2-d860-4e53-8451-b39ffd84524b\/readme.md"
}
```

Send it back later :

```html
<form action="/upload" method="post">
    {{ csrf_field() }}
    <input type="hidden" name="attachment_id" id="attachment_id">
    <button type="submit">Save</button>
</form>

<!-- Where attachment_id is populated on success -->

<script>
    Dropzone.options.myDropzone = {
        init: function () {
            this.on("success", function (file, response) {
                document.getElementById('attachment_id').value = response.uuid;
            });
        }
    };
</script>
```

Bind the value later :

```php
<?php

Route::post('/upload', function () {
    $model = App\User::first();

    Bnb\Laravel\Attachments\Attachment::attach(Request::input('attachment_id'), $model);

    return redirect('/dropzone');
});
```

### Delete

The route `attachments.dropzone.delete` can be called via HTTP `DELETE`.
 The attachment ID must be provided as parameter.

The delete action provided by this route **only works for pending attachement**
 (not bound to a model).

To prevent deletion of other users file, the current CSRF token is saved
 when uploading via the dropzone endpoint and it must be the same when
 calling the dropzone delete endpoint. This behavior can be deactivated
 via the configuration or env key (see [config/attachments.php](./config/attachments.php)).

Usage example :

```html
<script>
var MyDropzone = {
    url: "{{ route('attachments.dropzone.delete', ['id' => ':id']) }}"
    // ...
    deletedfile: function (file) {
        axios.delete(this.url.replace(/:id/, file.id)).then(function () {
            //...
        });
    }
    //...
}
</script>
```

### Events

Two event are fired by the dropzone endpoints controller :

- `attachments.dropzone.uploading` with the `$request : Request` as parameter
- `attachments.dropzone.deleting` with the `$request : Request` and
 the `$file : Attachement` as parameters

If one of the listeners returns false, the action is aborted.

```php
public function boot()
{
    Event::listen('attachments.dropzone.uploading', function ($request) {
        return $this->isAllowedToUploadFile($request);
    });

    Event::listen('attachments.dropzone.deleting', function ($request, $file) {
        return $this->canDeletePendingFile($request, $file);
    });
}
```

## Temporary URLs
It is possible to generate a unique temporary URL for downloading the attachments via the `getTemporaryUrl` method of the `Attachment` model, for sharing purposes foremost.  
The `getTemporaryUrl` method has one parameter : a `Carbon` date, after which the link will no longer be valid.

The default generated URL is of the form : `http://example.com/attachments/shared/<a very long string>`. The share path can be modified in the config file under the `shared_pattern` key.

## Cleanup commands

A command is provided to cleanup the attachments not bound to a model
 (when `model_type` and `model_id` are `null`).

    php artisan attachment:cleanup

The `-s` (or `--since=[timeInMinutes]`) option can be set to specify
 another time limit in minutes : only unbound files older than the
 specified age will be deleted. This value is set to **1440** by default.
 
## Customization

### Set a custom database connection name for the models

You can customize the database connection name by either :

* Adding an `.env` variable for `ATTACHMENTS_DATABASE_CONNECTION` (recommended) OR
* Changing the configuration option `attachments.database.connection` in `config/attachments.php`.
 
### Extends Attachment model columns

The configuration defines the list of fillable attachment attributes in the `attachment.attributes` key.
 
This allows you to create migration to add new columns in the attachment table 
 and declare them in your published config at `config/attachments.php`.

### Customize the attachment storage directory prefix

You may easily customize the folder/prefix where new attachments are stored by either:

* Adding an `.env` variable for `ATTACHMENTS_STORAGE_DIRECTORY_PREFIX` (recommended) OR
* Changing the configuration option `attachments.storage_directory.prefix` in `config/attachments.php`.

The default value is `attachments` and any trailing `/`s will be trimmed automatically.

### Customize the attachment storage filepath

If you don't want to use the default storage filepath generation, you can provide the `filepath` option (relative to the root of storage disk).
 It must contain the directory and filename. It's up to you to ensure that the provided filepath is not in conflict with another file.

```php
$model->attach('/foo/bar/pdf.pdf', ['filepath' => 'foo/bar/test.pdf']);
```

> This does not apply to attachments uploaded via the integrated DropZone controller. Only available for explicit attachments.

### Extending the Attachment model class

This can be helpful to add some relations to the attachment model.

Create your own model that extends `Bnb\Laravel\Attachments\Attachment` :

```php
<?php
namespace Foo\Models;

MyAttachment extends Bnb\Laravel\Attachments\Attachment
{
    public function someCustomRelation() {
        //
    }
}
```

Bind your model to the `Bnb\Laravel\Attachments\Contracts\AttachmentContract` interface in a service provider :

```php
    public function register()
    {
        // ...
        $this->app->bind(
            \Bnb\Laravel\Attachments\Contracts\AttachmentContract::class,
            \Foo\Models\MyAttachment::class
        );
        // ...
    }
```