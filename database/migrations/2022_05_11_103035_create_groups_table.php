<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('group', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('arena_id');
            $table->string('name')->nullable();
            $table->string('owner')->nullable();
            $table->string('address')->nullable();
            $table->string('description')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['arena_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('group');
    }
};
