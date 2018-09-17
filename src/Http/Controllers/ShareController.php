<?php

namespace Bnb\Laravel\Attachments\Http\Controllers;

use Bnb\Laravel\Attachments\Contracts\AttachmentContract;
use Carbon\Carbon;
use Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Lang;

class ShareController extends Controller
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

    public function download($token, Request $request)
    {
        try {
            $data = json_decode(Crypt::decryptString($token));
        } catch (DecryptException $e) {
            abort(404, Lang::get('attachments::messages.errors.file_not_found'));

            return;
        }

        $id = $data->id;
        $expire = $data->expire;

        if (Carbon::createFromTimestamp($expire)->isPast()) {
            abort(403, Lang::get('attachments::messages.errors.expired'));
        }

        $disposition = ($disposition = $request->input('disposition')) === 'inline' ? $disposition : 'attachment';

        if ($file = $this->model->where('uuid', $id)->first()) {
            /** @var AttachmentContract $file */
            if ( ! $file->output($disposition)) {
                abort(403, Lang::get('attachments::messages.errors.access_denied'));
            }
        }

        abort(404, Lang::get('attachments::messages.errors.file_not_found'));
    }
}