<?php

namespace App\Http\Controllers;

use App\Models\Action_log;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
		$this->action_log = new Action_log;
    }

    public function monthly(Request $request)
	{
		$validator = $this->validator($request, 'monthly');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

        $report = DB::table('fight AS A')
					->join('schedule AS B', 'A.schedule_id', '=', 'B.id')
					->select('B.datetime',
						DB::raw('IFNULL(SUM(A.meron_bet), 0) AS `meron_bet`'),
						DB::raw('IFNULL(SUM(A.wala_bet), 0) AS `wala_bet`'),
						DB::raw('IFNULL(SUM(A.draw_bet), 0) AS `draw_bet`'),
						DB::raw('IFNULL(SUM(A.meron_count), 0) AS `meron_count`'),
						DB::raw('IFNULL(SUM(A.wala_count), 0) AS `wala_count`'),
						DB::raw('IFNULL(SUM(A.draw_count), 0) AS `draw_count`'),
						DB::raw('IFNULL(SUM(A.total_amount), 0) AS `total_amount`'),
						DB::raw('IFNULL(SUM(A.draw_commission), 0) AS `draw_commission`'),
						DB::raw('IFNULL(SUM(A.total_commission), 0) AS `total_commission`'))
					->whereMonth('B.datetime', '=', $request->month)
					->whereYear('B.datetime', '=', $request->year)
					->groupBy('B.datetime')
					->get();

		$start_date = Carbon::createFromFormat('Y-m-d H:i:s', $request->year . '-' . $request->month . '-01 00:00:00');
		$end_date =Carbon::createFromFormat('Y-m-d H:i:s', $request->year . '-' . $request->month . '-01 00:00:00')->lastOfMonth();
		
		$data = [];
		while ($start_date->lessThanOrEqualTo($end_date)) {
			$found = FALSE;
			foreach ($report as $row) {
				if ($start_date->eq($row->datetime)) {
					$found = TRUE;
					array_push($data, $row);
				}
			}

			if (!$found) {
				array_push($data, [
					"datetime" => $start_date->toDateTimeString(),
					"meron_bet" => "0",
					"wala_bet" => "0",
					"draw_bet" => "0",
					"meron_count" => "0",
					"wala_count" =>"0",
					"draw_count" =>"0",
					"total_amount" => "0",
					"draw_commission" => "0",
					"total_commission" =>"0",
				]);
			}
            $start_date->addDay();
		}

        $this->action_log->create([
			'user_id' => auth('user')->user()->id,
            'controller' => 'ReportController',
            'function' => 'Monthly'
        ]);

		return response()->json([
			'data' => $data,
			'status' => 1
		]);
	}

	private function validator(Request $request, $x)
    {
    	//custom validation error messages.
        $messages = [
            'required' => 'The :attribute field is required.',
            'unique' => 'The :attribute field is already exist'
        ];
        //validate the request.
        if($x == 'monthly') {
        	$rules = [
	            'month' => 'required|integer|min:1|max:12',
	            'year' => 'required|integer|min:2020|max:2120',
	        ];
        }

        $validate =  Validator::make($request->all(), $rules, $messages);

		return $validate;
    }
}
