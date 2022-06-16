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
        Schema::create('schedule', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('arena_id');
            $table->bigInteger('user_id');
            $table->string('event_name');
            $table->decimal('rake_percentage', 5, 2)->default(5.00);
            $table->integer('total_fights');
            $table->decimal('min_payout', 5, 2)->default(100.00);
            $table->double('max_payout', 15, 2)->default(1000000000.00);
            $table->double('max_draw_bet', 15, 2)->default(5000.00);
            $table->integer('enable_draw_bet')->default(1);
            $table->decimal('draw_rake', 5, 2)->default(2);
            $table->integer('print_count')->default(0);
            $table->integer('enable_claiming')->default(1);
            $table->dateTime('datetime');
            $table->dateTime('open_at')->nullable();
            $table->dateTime('close_at')->nullable();
            $table->integer('status')->default(0);
            $table->timestamps();
            $table->softDeletes();

            
            $table->index(['arena_id']);
            $table->index(['user_id']);
            $table->index(['datetime']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('schedule');
    }
};
