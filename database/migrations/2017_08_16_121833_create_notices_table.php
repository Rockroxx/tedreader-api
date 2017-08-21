<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateNoticesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notices', function(Blueprint $table) {
            $table->increments('id');
            $table->string('reference', 15);
            $table->string('referenced', 15)->nullable();
            $table->string('slug')->unique();
            $table->string('lang', 2);
            $table->string('type', 1);
            $table->string('nature', 10);
            $table->date('published');
            $table->date('deadline')->nullable();
            $table->integer('value')->unsigned()->nullable();
            $table->string('currency', 3)->nullable();

            $table->timestamps();

            $table->index('reference');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('notices');
    }
}
