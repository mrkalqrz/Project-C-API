<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->bigInteger('user_type_id');
            $table->bigInteger('group_id')->nullable();
            $table->string('username');
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('password');
            $table->double('money', 15, 2)->default(0.00);
            $table->double('max_bet', 15, 2)->default(100000000.00);
            $table->double('max_draw_bet', 15, 2)->default(5000.00);
            $table->integer('pin')->nullable();
            $table->integer('status')->default(0);
            $table->dateTime('last_login')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['group_id']);
        });

        DB::table('user')->insert([
            [
                'id' => 1, 
                'user_type_id' => 1, 
                'group_id' => 0, 
                'username' => 'masteradmin', 
                'firstname' => 'masteradmin', 
                'lastname' => 'masteradmin', 
                'username' => 'masteradmin',
                'email' => 'masteradmin@gmail.com',
                'password' => '$2y$10$0U1jvvLL.zEBU8Ge8ZW55eicp/0IP/O3GFITThA3QSogMNP6Of0SC',
                'money' => 0.00,
                'status' => 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                'id' => 2, 
                'user_type_id' => 2, 
                'group_id' => 0, 
                'username' => 'admin', 
                'firstname' => 'admin', 
                'lastname' => 'admin', 
                'username' => 'admin',
                'email' => 'admin@gmail.com',
                'password' => '$2y$10$0U1jvvLL.zEBU8Ge8ZW55eicp/0IP/O3GFITThA3QSogMNP6Of0SC',
                'money' => 0.00,
                'status' => 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                'id' => 3, 
                'user_type_id' => 3, 
                'group_id' => 0, 
                'username' => 'declarator', 
                'firstname' => 'declarator', 
                'lastname' => 'declarator', 
                'username' => 'declarator',
                'email' => 'declarator@gmail.com',
                'password' => '$2y$10$0U1jvvLL.zEBU8Ge8ZW55eicp/0IP/O3GFITThA3QSogMNP6Of0SC',
                'money' => 0.00,
                'status' => 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                'id' => 4, 
                'user_type_id' => 4, 
                'group_id' => 0, 
                'username' => 'cashier', 
                'firstname' => 'cashier', 
                'lastname' => 'cashier', 
                'username' => 'cashier',
                'email' => 'cashier@gmail.com',
                'password' => '$2y$10$0U1jvvLL.zEBU8Ge8ZW55eicp/0IP/O3GFITThA3QSogMNP6Of0SC',
                'money' => 0.00,
                'status' => 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                'id' => 5, 
                'user_type_id' => 5, 
                'group_id' => 0, 
                'username' => 'player', 
                'firstname' => 'player', 
                'lastname' => 'player', 
                'username' => 'player',
                'email' => 'player@gmail.com',
                'password' => '$2y$10$0U1jvvLL.zEBU8Ge8ZW55eicp/0IP/O3GFITThA3QSogMNP6Of0SC',
                'money' => 0.00,
                'status' => 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ],
            [
                'id' => 6, 
                'user_type_id' => 6, 
                'group_id' => 0, 
                'username' => 'manager', 
                'firstname' => 'manager', 
                'lastname' => 'manager', 
                'username' => 'manager',
                'email' => 'manager@gmail.com',
                'password' => '$2y$10$0U1jvvLL.zEBU8Ge8ZW55eicp/0IP/O3GFITThA3QSogMNP6Of0SC',
                'money' => 0.00,
                'status' => 1,
                'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user');
    }
};
