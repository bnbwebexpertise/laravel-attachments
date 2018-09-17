<?php

namespace Bnb\Laravel\Attachments\Http\Controllers;

use Bnb\Laravel\Attachments\Attachment;
use Bnb\Laravel\Attachments\Contracts\AttachmentContract;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lang;

class DownloadController extends Controller
{
    /**
     * Attachment model
     *
     * @var AttachmentContract
     */
    protected $model;

    public function __construct(AttachmentContract $model)
    {
        $this->model = $model;
    }

    public function download($id, Request $request)
    {
        $disposition = ($disposition = $request->input('disposition')) === 'inline' ? $disposition : 'attachment';

        if ($file = $this->model->where('uuid', $id)->first()) {
            /** @var Attachment $file */
            if ( ! $file->output($disposition)) {
                abort(403, Lang::get('attachments::messages.errors.access_denied'));
            }
        }

        abort(404, Lang::get('attachments::messages.errors.file_not_found'));
    }
}