<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNoticeContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notice_contacts', function(Blueprint $table){
            $table->increments('id');
            $table->integer('notice_id');
            $table->string('official_name')->nullable();
            $table->string('name')->nullable();
            $table->string('type', 1);
            $table->string('country', 2)->nullable();
            $table->string('street')->nullable();
            $table->string('city')->nullable();
            $table->string('postal')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('fax')->nullable();

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
        Schema::drop('notice_contacts');
    }
}
