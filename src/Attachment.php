<?php

namespace Bnb\Laravel\Attachments;

use Carbon\Carbon;
use Crypt;
use File as FileHelper;
use Illuminate\Database\Eloquent\Model;
use Storage;
use Symfony\Component\HttpFoundation\File\File as FileObj;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @property int    id
 * @property string uuid
 * @property int    model_id
 * @property string model_type
 * @property string disk
 * @property string filepath  the full path on storage disk
 * @property string filename
 * @property string filetype
 * @property int    filesize
 * @property string key       must be unique across a model's attachments pool
 * @property string group     allows to group attachments
 * @property string title
 * @property string description
 * @property string preview_url
 * @property array  metadata
 * @property string extension the file extension (read-only mutator)
 * @property string path      the file directory (read-only mutator)
 * @property string url       the public URL (read-only mutator)
 *
 * @package   Bnb\Laravel\Attachments
 */
class Attachment extends Model
{

    protected $table = 'attachments';

    protected $guarded = ['filepath'];

    protected $casts = [
        'metadata' => 'array',
    ];

    protected $observables = ['outputting'];

    /*
     * Constructors
     */

    /**
     * Shortcut method to bind an attachment to a model
     *
     * @param string $uuid
     * @param Model  $model   a model that uses HasAttachment
     * @param array  $options filter options based on configuration key `attachments.attributes`
     *
     * @return Attachment|null
     */
    public static function attach($uuid, $model, $options = [])
    {
        /** @var Attachment $attachment */
        $attachment = self::where('uuid', $uuid)->first();

        if ( ! $attachment) {
            return null;
        }

        // The dz_session_key is set by the build-in DropzoneController for security check
        if ($attachment->metadata('dz_session_key')) {
            $meta = $attachment->metadata;

            unset($meta['dz_session_key']);

            $attachment->metadata = $meta;
        }

        $options = array_only($options, config('attachments.attributes'));

        $attachment->fill($options);

        return $attachment->model()->associate($model)->save() ? $attachment : null;
    }


    /**
     * Creates a file object from a file an uploaded file.
     *
     * @param UploadedFile $uploadedFile source file
     * @param string       $disk         target storage disk
     *
     * @return $this|null
     */
    public function fromPost($uploadedFile, $disk = null)
    {
        if ($uploadedFile === null) {
            return null;
        }

        $this->disk = $this->disk ?: ($disk ?: Storage::getDefaultDriver());
        $this->filename = $uploadedFile->getClientOriginalName();
        $this->filesize = $uploadedFile->getClientSize();
        $this->filetype = $uploadedFile->getMimeType();
        $this->filepath = $this->filepath ?: ($this->getStorageDirectory() . $this->getPartitionDirectory() . $this->getDiskName());
        $this->putFile($uploadedFile->getRealPath(), $this->filepath);

        return $this;
    }


    /**
     * Creates a file object from a file on the disk.
     *
     * @param string $filePath source file
     * @param string $disk     target storage disk
     *
     * @return $this|null
     */
    public function fromFile($filePath, $disk = null)
    {
        if ($filePath === null) {
            return null;
        }

        $file = new FileObj($filePath);

        $this->disk = $this->disk ?: ($disk ?: Storage::getDefaultDriver());
        $this->filename = $file->getFilename();
        $this->filesize = $file->getSize();
        $this->filetype = $file->getMimeType();
        $this->filepath = $this->filepath ?: ($this->getStorageDirectory() . $this->getPartitionDirectory() . $this->getDiskName());
        $this->putFile($file->getRealPath(), $this->filepath);

        return $this;
    }

    /*
     * Model handling
     */

    /**
     * Relationship: model
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model()
    {
        return $this->morphTo();
    }


    /**
     * Register an outputting model event with the dispatcher.
     *
     * @param  \Closure|string $callback
     *
     * @return void
     */
    public static function outputting($callback)
    {
        static::registerModelEvent('outputting', $callback);
    }


    /**
     * Setup behaviors
     */
    protected static function boot()
    {
        parent::boot();

        if (config('attachments.behaviors.cascade_delete')) {
            static::deleting(function ($attachment) {
                /** @var Attachment $attachment */

                $attachment->deleteFile();
            });
        }

        static::creating(function ($attachment) {
            /** @var Attachment $attachment */

            if (empty($attachment->uuid)) {
                throw new \Exception('Failed to generated an UUID value');
            }

            if (empty($attachment->key)) {
                $attachment->key = uniqid();
            }
        });
    }


    public function getUuidAttribute()
    {
        if ( ! empty($this->attributes['uuid'])) {
            return $this->attributes['uuid'];
        }

        $generator = config('attachments.uuid_provider');

        if (strpos($generator, '@') !== false) {
            $generator = explode('@', $generator, 2);
        }

        if ( ! is_array($generator) && function_exists($generator)) {
            return $this->uuid = call_user_func($generator);
        }

        if (is_callable($generator)) {
            return $this->uuid = forward_static_call($generator);
        }

        throw new \Exception('Missing UUID provider configuration for attachments');
    }


    public function getExtensionAttribute()
    {
        return $this->getExtension();
    }


    public function getPathAttribute()
    {
        return pathinfo($this->filepath, PATHINFO_DIRNAME);
    }


    public function getUrlAttribute()
    {
        if ($this->isLocalStorage()) {
            $extension = $this->extension;

            return route('attachments.download', [
                'id' => $this->uuid,
                'name' => $extension ?
                    str_slug(substr($this->filename, 0, -1 * strlen($this->extension) - 1)) . '.' . $this->extension :
                    str_slug($this->filename)
            ]);
        } else {
            return Storage::disk($this->disk)->url($this->filepath);
        }
    }


    public function toArray()
    {
        $attributes = parent::toArray();

        return array_merge($attributes, [
            'url' => $this->url
        ]);
    }


    /*
     * File handling
     */

    public function output($disposition = 'inline')
    {
        if ($this->fireModelEvent('outputting') === false) {
            return false;
        }

        header("Content-type: " . $this->filetype);
        header('Content-Disposition: ' . $disposition . '; filename="' . $this->filename . '"');
        header('Cache-Control: private');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: pre-check=0, post-check=0, max-age=0');
        header('Accept-Ranges: bytes');
        header('Content-Length: ' . $this->filesize);

        exit($this->getContents());
    }


    /**
     * Get file contents from storage device.
     */
    public function getContents()
    {
        return $this->storageCommand('get', $this->filepath);
    }


    /**
     * Get a metadata value by key with dot notation
     *
     * @param string $key     The metadata key, supports dot notation
     * @param mixed  $default The default value to return if key is not found
     *
     * @return array|mixed
     */
    public function metadata($key, $default = null)
    {
        if (is_null($key)) {
            return $this->metadata;
        }

        return array_get($this->metadata, $key, $default);
    }


    /**
     * Saves a file
     *
     * @param string $sourcePath An absolute local path to a file name to read from.
     * @param string $filePath   A storage file path to save to.
     *
     * @return bool
     */
    protected function putFile($sourcePath, $filePath = null)
    {
        if ( ! $filePath) {
            $filePath = $this->filepath;
        }

        if ( ! $this->isLocalStorage()) {
            return $this->copyToStorage($sourcePath, $filePath);
        }

        $destinationPath = $this->getLocalRootPath() . '/' . pathinfo($filePath, PATHINFO_DIRNAME) . '/';

        if (
            ! FileHelper::isDirectory($destinationPath) &&
            ! FileHelper::makeDirectory($destinationPath, 0777, true, true) &&
            ! FileHelper::isDirectory($destinationPath)
        ) {
            trigger_error(error_get_last()['message'], E_USER_WARNING);
        }

        return FileHelper::copy($sourcePath, $destinationPath . basename($filePath));
    }


    protected function deleteFile()
    {
        $this->storageCommand('delete', $this->filepath);
        $this->deleteEmptyDirectory($this->path);
    }


    /**
     * Generates a disk name from the supplied file name.
     */
    protected function getDiskName()
    {
        if ($this->filepath !== null) {
            return $this->filepath;
        }

        $ext = strtolower($this->getExtension());
        $name = str_replace('.', '', $this->uuid);

        return $this->filepath = $ext !== null ? $name . '.' . $ext : $name;
    }


    /**
     * Returns the file extension.
     */
    public function getExtension()
    {
        return FileHelper::extension($this->filename);
    }


    /**
     * Generate a temporary url at which the current file can be downloaded until $expire
     *
     * @param Carbon $expire
     *
     * @return string
     */
    public function getTemporaryUrl(Carbon $expire)
    {

        $payload = Crypt::encryptString(collect([
            'id' => $this->uuid,
            'expire' => $expire->getTimestamp(),
            'shared_at' => Carbon::now()->getTimestamp()
        ])->toJson());

        return route('attachments.download-shared', ['token' => $payload]);

    }


    /**
     * Generates a partition for the file.
     * return /ABC/DE1/234 for an name of ABCDE1234.
     *
     * @return mixed
     */
    protected function getPartitionDirectory()
    {
        return implode('/', array_slice(str_split($this->uuid, 3), 0, 3)) . '/';
    }


    /**
     * Define the internal storage path, override this method to define.
     */
    protected function getStorageDirectory()
    {
        return config('attachments.storage_directory.prefix', 'attachments') . '/';
    }


    /**
     * If working with local storage, determine the absolute local path.
     *
     * @return string
     */
    protected function getLocalRootPath()
    {
        return storage_path() . '/app';
    }


    /**
     * Returns true if the storage engine is local.
     *
     * @return bool
     */
    protected function isLocalStorage()
    {
        return $this->disk == 'local';
    }


    /**
     * Returns true if a directory contains no files.
     *
     * @param string|null $dir the directory path
     *
     * @return bool
     */
    protected function isDirectoryEmpty($dir)
    {
        if ( ! $dir || ! $this->storageCommand('exists', $dir)) {
            return null;
        }

        return count($this->storageCommand('allFiles', $dir)) === 0;
    }


    /**
     * Copy the local file to Storage
     *
     * @param string $localPath
     * @param string $storagePath
     *
     * @return bool
     */
    protected function copyToStorage($localPath, $storagePath)
    {
        return Storage::disk($this->disk)->put($storagePath, FileHelper::get($localPath));
    }


    /**
     * Checks if directory is empty then deletes it,
     * three levels up to match the partition directory.
     *
     * @param string|null $dir the directory path
     *
     * @return void
     */
    protected function deleteEmptyDirectory($dir = null)
    {
        if ( ! $this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCommand('deleteDirectory', $dir);

        $dir = dirname($dir);

        if ( ! $this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCommand('deleteDirectory', $dir);

        $dir = dirname($dir);

        if ( ! $this->isDirectoryEmpty($dir)) {
            return;
        }

        $this->storageCommand('deleteDirectory', $dir);
    }


    /**
     * Calls a method against File or Storage depending on local storage.
     * This allows local storage outside the storage/app folder and is
     * also good for performance. For local storage, *every* argument
     * is prefixed with the local root path.
     *
     * @param string $string   the command string
     * @param string $filepath the path on storage
     *
     * @return mixed
     */
    protected function storageCommand($string, $filepath)
    {
        $args = func_get_args();
        $command = array_shift($args);

        if ($this->isLocalStorage()) {
            $interface = 'File';
            $path = $this->getLocalRootPath();
            $args = array_map(function ($value) use ($path) {
                return $path . '/' . $value;
            }, $args);
        } else {
            if (substr($filepath, 0, 1) !== '/') {
                $args[0] = $filepath = '/' . $filepath;
            }

            $interface = Storage::disk($this->disk);
        }

        return forward_static_call_array([$interface, $command], $args);
    }
}