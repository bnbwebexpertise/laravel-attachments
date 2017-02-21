<?php

namespace Bnb\Laravel\Attachments\Http\Controllers;

use Bnb\Laravel\Attachments\Attachment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lang;

class UploadController extends Controller
{

    public function dropzone(Request $request)
    {
        $attachment = (new Attachment(array_only($request->input(), [
            'title',
            'description',
            'key'
        ])))
            ->fromPost($request->file($request->input('file_key', 'file')));

        if ($attachment->save()) {
            return array_only($attachment->toArray(), [
                'uuid',
                'url',
                'filename',
                'filetype',
                'filesize',
                'title',
                'description',
                'key'
            ]);
        }

        abort(500, Lang::get('attachments::messages.errors.upload_failed'));
    }
}