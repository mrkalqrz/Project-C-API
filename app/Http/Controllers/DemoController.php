<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Action_log;
use Illuminate\Support\Facades\DB;

class DemoController extends Controller
{
    public function __construct()
    {
		$this->action_log = new Action_log;
    }

    public function info(Request $request)
    {
        $data = DB::table('bet_log')
					->select('schedule_id', 'fight_id',
						DB::raw('IFNULL(SUM(CASE bet_select WHEN 1 THEN bet_amount END), 0) AS `meron_bet`'),
						DB::raw('IFNULL(SUM(CASE bet_select WHEN 2 THEN bet_amount END), 0) AS `wala_bet`'),
						DB::raw('IFNULL(SUM(CASE bet_select WHEN 3 THEN bet_amount END), 0) AS `draw_bet`'),
						DB::raw('IFNULL(COUNT(CASE bet_select WHEN 1 THEN id END), 0) AS `meron_count`'),
						DB::raw('IFNULL(COUNT(CASE bet_select WHEN 2 THEN id END), 0) AS `wala_count`'),
						DB::raw('IFNULL(COUNT(CASE bet_select WHEN 3 THEN id END), 0) AS `draw_count`')
                        )
					->where('fight_id', '=', $request->fight_id)
					->groupBy('fight_id', 'schedule_id')
					->first();

        if ( ! $data) {
            return response()->json([
                'status' => 0
            ]);
        }

        $schedule = DB::table('schedule')->where('id', $data->schedule_id)->first();
        
        $total_bet = $data->meron_bet + $data->wala_bet;
        $rake = (double)$schedule->rake_percentage / 100;
        $owner_profit = $total_bet * $rake;
        $base_total = $total_bet - $owner_profit;
        $data->meron_payout = (double)number_format(($base_total / $data->meron_bet) * 100, 2);
        $data->wala_payout = (double)number_format(($base_total / $data->wala_bet) * 100, 2);
        $data->est_draw_distribution = $data->draw_bet * $schedule->draw_rake;
        $data->est_total_commission = $owner_profit;
        
        $this->action_log->create([
            '_id' => $data->id,
            'controller' => 'DemoController',
            'function' => 'Info'
        ]);

        return response()->json([
            'data' => $data,
            'status' => 1
        ]);
    }
}
