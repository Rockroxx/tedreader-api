<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTarLocationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tar_locations', function(Blueprint $table){
            $table->increments('id');
            $table->string('tar');
            $table->string('location');
            $table->integer('start');
            $table->integer('end');
            $table->integer('year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('tar_locations');
    }
}
