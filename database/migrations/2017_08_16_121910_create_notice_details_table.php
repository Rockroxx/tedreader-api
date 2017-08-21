<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNoticeDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notice_details', function(Blueprint $table){
            $table->increments('id');
            $table->integer('notice_id');

            $table->text('title')->nullable();
            $table->text('description')->nullable();

            $table->string('body');
            $table->string('document_url')->nullable();
            $table->string('body_url')->nullable();
            $table->string('tendering_url')->nullable();

            $table->index('notice_id');

        });
        app('db')->statement('ALTER TABLE notice_details ADD FULLTEXT full(title, description)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('notice_details');
    }
}
