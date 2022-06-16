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
        Schema::create('user_type', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('user_type')->insert([
            ['id' => 1, 'name' => 'masteradmin'],
            ['id' => 2, 'name' => 'admin'],
            ['id' => 3, 'name' => 'declarator'],
            ['id' => 4, 'name' => 'cashier'],
            ['id' => 5, 'name' => 'player'],
            ['id' => 6, 'name' => 'manager']
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_type');
    }
};
