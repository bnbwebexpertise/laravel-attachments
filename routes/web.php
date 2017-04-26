<?php

Route::group([
    'prefix' => config('attachments.routes.prefix'),
    'middleware' => config('attachments.routes.middleware')
], function () {
    Route::get(config('attachments.routes.shared_pattern'), 'Bnb\Laravel\Attachments\Http\Controllers\ShareController@download')
        ->where('token', '.+')
        ->where('id', '[a-zA-Z0-9-]+')
        ->where('name', '.+')
        ->name('attachments.download-shared');
    Route::get(config('attachments.routes.pattern'), 'Bnb\Laravel\Attachments\Http\Controllers\DownloadController@download')
        ->where('id', '[a-zA-Z0-9-]+')
        ->where('name', '.+')
        ->name('attachments.download');

    Route::post(config('attachments.routes.dropzone.upload_pattern'), 'Bnb\Laravel\Attachments\Http\Controllers\DropzoneController@post')
        ->name('attachments.dropzone');

    Route::delete(config('attachments.routes.dropzone.delete_pattern'), 'Bnb\Laravel\Attachments\Http\Controllers\DropzoneController@delete')
        ->where('id', '[a-zA-Z0-9-]+')
        ->where('name', '.+')
        ->name('attachments.dropzone.delete');
});