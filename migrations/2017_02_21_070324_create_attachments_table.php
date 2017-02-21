<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttachmentsTable extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attachments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid', 64);
            $table->unsignedInteger('model_id');
            $table->string('model_class', 512);
            $table->string('drive', 32);
            $table->string('filepath', 512);
            $table->string('filename', 255);
            $table->string('filetype', 64);
            $table->unsignedInteger('filesize');
            $table->string('title', 92)->nullable();
            $table->text('description')->nullable();
            $table->string('preview_url', 512)->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();

            $table->index(['model_class', 'model_id'], 'idx_attachement_model');
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attachments', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token')->index();
            $table->timestamp('created_at')->nullable();
        });
    }
}
