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
        Schema::create('bet_log', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_id');
            $table->bigInteger('schedule_id');
            $table->bigInteger('fight_id');
            $table->integer('bet_select');
            $table->double('bet_amount', 15, 6);
            $table->integer('status')->default(0);
            $table->integer('result')->nullable();
            $table->decimal('result_amount', 15, 6)->nullable();
            $table->string('barcode');
            $table->integer('claimed')->default(0);
            $table->integer('reprint_count')->default(0);
            $table->string('remark')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id']);
            $table->index(['schedule_id']);
            $table->index(['fight_id']);
            $table->index(['schedule_id', 'fight_id']);
            $table->index(['schedule_id', 'fight_id', 'bet_select']);
            $table->index(['bet_select']);
            $table->index(['status']);
            $table->index(['result']);
            $table->index(['barcode']);
            $table->index(['claimed']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bet_log');
    }
};
