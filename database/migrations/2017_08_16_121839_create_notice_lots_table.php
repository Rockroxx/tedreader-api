<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNoticeLotsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notice_lots', function(Blueprint $table){
            $table->increments('id');
            $table->integer('notice_id');

            $table->text('title')->nullable();
            $table->text('description')->nullable();

            $table->float('duration')->nullable();
            $table->string('duration_type', 10)->nullable();
            $table->float('value')->nullable();
            $table->string('currency', 3)->nullable();

            $table->index('notice_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('notice_lots');
    }
}
