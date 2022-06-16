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
        Schema::create('fight', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('arena_id');
            $table->bigInteger('schedule_id');
            $table->bigInteger('admin_id');
            $table->bigInteger('fight_no')->default(0);
            $table->decimal('rake_percentage', 5, 2)->default(5.00);
            $table->integer('result')->default(0);
            $table->string('announcement')->nullable();
            $table->integer('confirmed')->default(0);
            $table->string('meron_img')->nullable();
            $table->string('wala_img')->nullable();
            $table->decimal('meron_bet', 15, 6)->default(0.00);
            $table->decimal('wala_bet', 15, 6)->default(0.00);
            $table->decimal('draw_bet', 15, 6)->default(0.00);
            $table->integer('meron_count')->default(0);
            $table->integer('wala_count')->default(0);
            $table->integer('draw_count')->default(0);
            $table->decimal('meron_payout', 15, 6)->default(0.00);
            $table->decimal('wala_payout', 15, 6)->default(0.00);
            $table->decimal('total_amount', 15, 6)->default(0.00);
            $table->decimal('draw_commission', 15, 6)->default(0.00);
            $table->decimal('total_commission', 15, 6)->default(0.00);
            $table->integer('status')->default(1);
            $table->integer('regrade_count')->default(0);
            $table->integer('claim_status')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['arena_id']);
            $table->index(['schedule_id']);
            $table->index(['admin_id']);
            $table->index(['fight_no']);
            $table->index(['result']);
            $table->index(['confirmed']);
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
        Schema::dropIfExists('fight');
    }
};
