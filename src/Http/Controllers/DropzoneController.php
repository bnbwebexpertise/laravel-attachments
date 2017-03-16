<?php

namespace Bnb\Laravel\Attachments\Http\Controllers;

use Bnb\Laravel\Attachments\Attachment;
use Event;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lang;

class DropzoneController extends Controller
{

    public function post(Request $request)
    {
        if (Event::dispatch('attachements.dropzone.uploading', [$request], true) === false) {
            return response(['status' => 403, 'message' => Lang::get('attachments::messages.errors.upload_denied')], 403);
        }

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

        return response(['status' => 500, 'message' => Lang::get('attachments::messages.errors.upload_failed')], 500);
    }


    public function delete($id, Request $request)
    {
        if ($file = Attachment::where('uuid', $id)->first()) {
            /** @var Attachment $file */

            if (Event::dispatch('attachements.dropzone.deleting', [$request, $file], true) === false) {
                return response(['status' => 403, 'message' => Lang::get('attachments::messages.errors.delete_denied')], 403);
            }

            $file->delete();
        }

        return response('', 204);
    }
}