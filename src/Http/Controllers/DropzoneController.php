<?php

namespace Bnb\Laravel\Attachments\Http\Controllers;

use Bnb\Laravel\Attachments\Attachment;
use Event;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lang;
use Log;

class DropzoneController extends Controller
{

    public function post(Request $request)
    {
        if (Event::dispatch('attachments.dropzone.uploading', [$request], true) === false) {
            return response(Lang::get('attachments::messages.errors.upload_denied'), 403);
        }

        $file = (new Attachment(array_only($request->input(), [
            'title',
            'description',
            'key'
        ])))
            ->fromPost($request->file($request->input('file_key', 'file')));

        $file->metadata = ['dz_session_key' => csrf_token()];

        try {
            if ($file->save()) {
                return array_only($file->toArray(), [
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
        } catch (Exception $e) {
            Log::error('Failed to upload attachment : ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }

        return response(Lang::get('attachments::messages.errors.upload_failed'), 500);
    }


    public function delete($id, Request $request)
    {
        try {
            if ($file = Attachment::where('uuid', $id)->first()) {
                /** @var Attachment $file */

                if ($file->model_type || $file->model_id) {
                    return response(Lang::get('attachments::messages.errors.delete_denied'), 422);
                }

                if (filter_var(config('attachments.behaviors.dropzone_check_csrf'), FILTER_VALIDATE_BOOLEAN) &&
                    $file->metadata('dz_session_key') !== csrf_token()
                ) {
                    return response(Lang::get('attachments::messages.errors.delete_denied'), 401);
                }

                if (Event::dispatch('attachments.dropzone.deleting', [$request, $file], true) === false) {
                    return response(Lang::get('attachments::messages.errors.delete_denied'), 403);
                }

                $file->delete();
            }

            return response('', 204);
        } catch (Exception $e) {
            Log::error('Failed to delete attachment : ' . $e->getMessage(), ['id' => $id, 'trace' => $e->getTraceAsString()]);

            return response(Lang::get('attachments::messages.errors.delete_failed'), 500);
        }
    }
}