<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ArenaController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\FightController;
use App\Http\Controllers\BetLogController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ActionLogController;
use App\Http\Controllers\UserTypeController;
use App\Http\Controllers\SyncController;

Route::get('/', function () {
    return '404 not found';
});

$router->get('/demo/info', [DemoController::class, 'info']);

$router->post('/login', [AuthController::class, 'login']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/profile', [AuthController::class, 'profile']);
$router->post('/logout', [AuthController::class, 'logout']);

$router->group(['middleware' => 'auth:masteradmin'], function () use ($router) {
});

$router->group(['middleware' => 'auth:masteradmin,admin,manager'], function () use ($router) {
    $router->get('/sync', [SyncController::class, 'sync']);
    $router->group(['prefix' => '/arena'], function () use ($router) {
        $router->post('/get', [ArenaController::class, 'get']);
        $router->post('/list', [ArenaController::class, 'list']);
        $router->post('/create', [ArenaController::class, 'create']);
        $router->post('/edit', [ArenaController::class, 'edit']);
        $router->post('/update', [ArenaController::class, 'update']);
        $router->post('/delete', [ArenaController::class, 'delete']);
    });
    $router->group(['prefix' => '/group'], function () use ($router) {
        $router->post('/get', [GroupController::class, 'get']);
        $router->post('/list', [GroupController::class, 'list']);
        $router->post('/create', [GroupController::class, 'create']);
        $router->post('/edit', [GroupController::class, 'edit']);
        $router->post('/update', [GroupController::class, 'update']);
        $router->post('/delete', [GroupController::class, 'delete']);
    });
    $router->group(['prefix' => '/report'], function () use ($router) {
        $router->post('/monthly', [ReportController::class, 'monthly']);
    });
    $router->group(['prefix' => '/user'], function () use ($router) {
        $router->post('/get', [UserController::class, 'get']);
        $router->post('/list', [UserController::class, 'list']);
        $router->post('/create', [UserController::class, 'create']);
        $router->post('/edit', [UserController::class, 'edit']);
        $router->post('/update', [UserController::class, 'update']);
        $router->post('/delete', [UserController::class, 'delete']);
        $router->post('/update-status', [UserController::class, 'updateStatus']);
        $router->post('/update-password', [UserController::class, 'updatePassword']);
    });
    $router->group(['prefix' => '/setting'], function () use ($router) {
        $router->post('/list', [SettingController::class, 'list']);
        $router->post('/create', [SettingController::class, 'create']);
        $router->post('/update', [SettingController::class, 'update']);
    });
    $router->group(['prefix' => '/action-log'], function () use ($router) {
        $router->post('/get', [ActionLogController::class, 'get']);
        $router->post('/list', [ActionLogController::class, 'list']);
    });
    $router->group(['prefix' => '/user-type'], function () use ($router) {
        $router->post('/get', [UserTypeController::class, 'get']);
        $router->post('/list', [UserTypeController::class, 'list']);
        $router->post('/create', [UserTypeController::class, 'create']);
    });
});

$router->group(['middleware' => 'auth:masteradmin,admin,cashier,manager,player'], function () use ($router) {
    $router->group(['prefix' => '/transaction'], function () use ($router) {
        $router->post('/get', [TransactionController::class, 'get']);
        $router->post('/list', [TransactionController::class, 'list']);
        $router->post('/create', [TransactionController::class, 'create']);
        $router->post('/update', [TransactionController::class, 'update']);
        $router->post('/upload-image', [TransactionController::class, 'uploadImage']);
    });
});

$router->group(['middleware' => 'auth:masteradmin,admin,declarator,cashier,player,manager'], function () use ($router) {
    $router->group(['prefix' => '/fight'], function () use ($router) {
        $router->post('/get', [FightController::class, 'get']);
        $router->post('/get-current', [FightController::class, 'getCurrent']);
        $router->post('/list', [FightController::class, 'list']);
        $router->post('/create', [FightController::class, 'create']);
        $router->post('/edit', [FightController::class, 'edit']);
        $router->post('/update', [FightController::class, 'update']);
        $router->post('/delete', [FightController::class, 'delete']);
        $router->post('/update-status', [FightController::class, 'updateStatus']);
        $router->post('/update-bet', [FightController::class, 'updateBet']);
        $router->post('/update-result', [FightController::class, 'updateResult']);
        $router->post('/regrade', [FightController::class, 'regrade']);
        $router->post('/update-claim-status', [FightController::class, 'updateClaimStatus']);
        $router->post('/update-announcement', [FightController::class, 'updateAnnouncement']);
    });
    $router->group(['prefix' => '/schedule'], function () use ($router) {
        $router->post('/get', [ScheduleController::class, 'get']);
        $router->post('/get-current', [ScheduleController::class, 'getCurrent']);
        $router->post('/list', [ScheduleController::class, 'list']);
        $router->post('/create', [ScheduleController::class, 'create']);
        $router->post('/edit', [ScheduleController::class, 'edit']);
        $router->post('/update', [ScheduleController::class, 'update']);
        $router->post('/delete', [ScheduleController::class, 'delete']);
    });
});

$router->group(['middleware' => 'auth:masteradmin,admin,cashier,player,manager'], function () use ($router) {
    $router->group(['prefix' => '/bet'], function () use ($router) {
        $router->post('/get', [BetLogController::class, 'get']);
        $router->post('/user-bet', [BetLogController::class, 'userBet']);
        $router->post('/scan', [BetLogController::class, 'scan']);
        $router->post('/claim', [BetLogController::class, 'claim']);
        $router->post('/list', [BetLogController::class, 'list']);
        $router->post('/create', [BetLogController::class, 'create']);
        $router->post('/edit', [BetLogController::class, 'edit']);
        $router->post('/update', [BetLogController::class, 'update']);
        $router->post('/delete', [BetLogController::class, 'delete']);
    });
    $router->group(['prefix' => '/transaction'], function () use ($router) {
        $router->post('/deposit', [TransactionController::class, 'deposit']);
        $router->post('/withdraw', [TransactionController::class, 'withdraw']);
    });
});