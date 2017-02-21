<?php
/**
 * laravel
 *
 * @author    Jérémy GAULIN <jeremy@bnb.re>
 * @copyright 2017 - B&B Web Expertise
 */

namespace Bnb\Laravel\Attachments\Http\Controllers;

use Bnb\Laravel\Attachments\Attachment;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class DownloadController extends Controller
{

    public function download($id, Request $request)
    {
        $disposition = ($disposition = $request->input('disposition')) === 'inline' ? $disposition : 'attachement';

        if ($file = Attachment::whereUuid($id)->first()) {
            /** @var Attachment $file */
            return $file->output($disposition);
        }

        abort(404);
    }
}