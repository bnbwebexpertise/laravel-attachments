<?php

namespace Bnb\Laravel\Attachments\Http\Controllers;

use Bnb\Laravel\Attachments\Attachment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lang;

class DownloadController extends Controller
{

    public function download($id, Request $request)
    {
        $disposition = ($disposition = $request->input('disposition')) === 'inline' ? $disposition : 'attachment';

        if ($file = Attachment::where('uuid', $id)->first()) {
            /** @var Attachment $file */
            if ( ! $file->output($disposition)) {
                abort(403, Lang::get('attachments::messages.errors.access_denied'));
            }
        }

        abort(404, Lang::get('attachments::messages.errors.file_not_found'));
    }
}