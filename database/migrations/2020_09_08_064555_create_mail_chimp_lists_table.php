<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMailChimpListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_chimp_lists', function (Blueprint $table) {
            $table->string('campaign_defaults');
            $table->string('contact');
            $table->string('email_type_option');
            $table->string('id');
            $table->string('mail_chimp_id');
            $table->string('name');
            $table->string('notify_on_subscribe');
            $table->string('notify_on_unsubscribe');
            $table->string('permission_reminder');
            $table->string('use_archive_bar');
            $table->string('visibility');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mail_chimp_lists');
    }
}
