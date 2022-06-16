<?php

namespace App\Http\Controllers;

use App\Models\Buss_user;
use App\Models\User_bk;
use App\Models\Transaction;
use App\Models\Transaction_bk;
use App\Models\Bet_log;
use App\Models\Bet_log_bk;
use App\Models\Action_log;
use Illuminate\Http\Request;

class SyncController extends Controller
{
    public function __construct()
    {
		$this->action_log = new Action_log;
    }

    public function sync()
    {
        // User Table

        $last_user_bk = User_bk::orderBy('updated_at', 'desc')->first(); 
        if($last_user_bk) {
            $user = Buss_user::where('updated_at', '>=', $last_user_bk->updated_at)->get()->toArray();
        } else {
            $user = Buss_user::all()->toArray();
        }

        $user_bk = User_bk::upsert($user, [
            'user_type_id',
            'group_id',
            'username',
            'firstname',
            'lastname',
            'email',
            'phone',
            'password',
            'money',
            'max_bet',
            'max_draw_bet',
            'pin',
            'last_login',
            'status'
        ]);

        if($user_bk > 0) {
            $user_sync = "Sync User is Complete!";
        } else {
            $user_sync = "There's no User to sync!";
        }
        
        // Transaction Table

        $last_transaction_bk = Transaction_bk::orderBy('updated_at', 'desc')->first(); 
        if($last_transaction_bk) {
            $transaction = Transaction::where('updated_at', '>=', $last_transaction_bk->updated_at)->get()->toArray();
        } else {
            $transaction = Transaction::all()->toArray();
        }

        $transaction_bk = Transaction_bk::upsert($transaction, [
            'user_id',
            'user_type_id',
            'type',
            'amount',
            'image',
            'note',
            'status'
        ]);

        if($transaction_bk > 0) {
            $transaction_sync = "Sync Transaction is Complete!";
        } else {
            $transaction_sync = "There's no Transaction to sync!";
        }
        
        // Bet Log Table

        $last_bet_log_bk = Bet_log_bk::orderBy('updated_at', 'desc')->first(); 
        if($last_bet_log_bk) {
            $bet_log = Bet_log::where('updated_at', '>=', $last_bet_log_bk->updated_at)->get()->toArray();
        } else {
            $bet_log = Bet_log::all()->toArray();
        }

        $bet_log_bk = Bet_log_bk::upsert($bet_log, [
            'user_id',
            'schedule_id',
            'fight_id',
            'bet_select',
            'bet_amount',
            'status',
            'result',
            'result_amount',
            'barcode',
            'claimed',
            'reprint_count',
            'remark'
        ]);

        if($bet_log_bk > 0) {
            $bet_log_sync = "Sync Bet log is Complete!";
        } else {
            $bet_log_sync = "There's no Bet Log to sync!";
        }

        $this->action_log->create([
            'user_id' => auth('user')->user()->id,
            '_id' => 1,
            'controller' => 'SyncController',
            'function' => 'Sync'
        ]);

        return response()->json([
            'user' => $user_sync,
            'transaction' => $transaction_sync,
            'bet_log' => $bet_log_sync,
			'status' => 1
		]);
    }
}