<?php

Route::group([
    'prefix' => config('attachments.routes.prefix'),
    'middleware' => config('attachments.routes.middleware')
], function () {
    Route::get(config('attachments.routes.pattern'), 'Bnb\Laravel\Attachments\Http\Controllers\DownloadController@download')
        ->where('id', '[a-zA-Z0-9-]+')
        ->where('name', '.+')
        ->name('attachments.download');

    Route::post('dropzone', 'Bnb\Laravel\Attachments\Http\Controllers\UploadController@dropzone')
        ->name('attachments.dropzone');
});