<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNoticeAwardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notice_awards', function(Blueprint $table){
            $table->increments('id');
            $table->integer('notice_id');

            $table->integer('lot')->nullable();

            $table->string('currency', 3)->nullable();
            $table->float('value')->nullable();

            $table->date('awarded_at');

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
        Schema::drop('notice_awards');
    }
}
