<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNoticeCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notice_categories', function(Blueprint $table){
            $table->increments('id');
            $table->integer('notice_id');
            $table->integer('category_id');

            $table->index('notice_id', 'notice_id_index');
            $table->index('category_id', 'notice_cat_id_index');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('notice_categories');
    }
}
