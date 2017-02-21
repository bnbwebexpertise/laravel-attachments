<?php
/**
 * laravel
 *
 * @author    Jérémy GAULIN <jeremy@bnb.re>
 * @copyright 2017 - B&B Web Expertise
 */

namespace Bnb\Laravel\Attachments;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{

    public function save($options = [])
    {
        if (empty($this->uuid)) {
            $this->uuid = $this->generateUuid();
        }

        parent::save($options);
    }


    protected function generateUuid()
    {
        $generator = config('attachements.uuid_provider');

        if (is_callable($generator)) {
            return $generator($this);
        }

        throw new \Exception('Missing UUID provider configuration for attachements');
    }


    public static function defaultUuid()
    {
        return uniqid();
    }
}