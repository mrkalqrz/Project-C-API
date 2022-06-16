<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('setting', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('type');
            $table->string('name');
            $table->integer('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('setting')->insert([
            ['type' => 'Admin Deposit Confirmation', 'name' => 'Admin Deposit Confirmation'],
            ['type' => 'Admin Withdraw Confirmation', 'name' => 'Admin Withdraw Confirmation']
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('setting');
    }
};
