<?php
/**
 * laravel
 *
 * @author    Jérémy GAULIN <jeremy@bnb.re>
 * @copyright 2017 - B&B Web Expertise
 */

namespace Bnb\Laravel\Attachments\Console\Commands;

use Bnb\Laravel\Attachments\Contracts\AttachmentContract;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Lang;
use Symfony\Component\Console\Input\InputOption;

class CleanupAttachments extends Command
{
    /**
     * Attachment model
     *
     * @var AttachmentContract
     */
    protected $model;

    protected $signature = 'attachments:cleanup';

    public function __construct(AttachmentContract $model)
    {
        parent::__construct();

        $this->model = $model;

        $this->setDescription(Lang::get('attachments::messages.console.cleanup_description'));

        $this->getDefinition()->addOption(new InputOption('since', '-s', InputOption::VALUE_OPTIONAL,

        Lang::get('attachments::messages.console.cleanup_option_since'), 1440));
    }


    public function handle()
    {
        if ($this->confirm(Lang::get('attachments::messages.console.cleanup_confirm'))) {
            $query = $this->model
                ->whereNull('model_type')
                ->whereNull('model_id')
                ->where('updated_at', '<=', Carbon::now()->addMinutes(-1 * $this->option('since')));

            $progress = $this->output->createProgressBar($count = $query->count());

            if ($count) {
                $query->chunk(100, function ($attachments) use ($progress) {
                    /** @var Collection $attachments */
                    $attachments->each(function ($attachment) use ($progress) {
                        /** @var AttachmentContract $attachment */
                        $attachment->delete();

                        $progress->advance();
                    });
                });

                $this->info(Lang::get('attachments::messages.console.done'));
            } else {
                $this->comment(Lang::get('attachments::messages.console.cleanup_no_data'));
            }
        }
    }
}