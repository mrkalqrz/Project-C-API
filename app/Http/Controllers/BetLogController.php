<?php

namespace App\Http\Controllers;

use App\Models\Bet_log;
use App\Models\User;
use App\Models\Action_log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BetLogController extends Controller
{
    public function __construct()
    {
		$this->action_log = new Action_log;
    }

    public function get(Request $request)
	{
		$validator = $this->validator($request, 'get');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

		$bet_log = Bet_log::with(['fight'])->find($request->id);

		$status = 0;

		if($bet_log) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $bet_log->id,
				'controller' => 'BetLogController',
				'function' => 'Get'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $bet_log,
			'status' => $status
		]);
	}
	
	public function userBet(Request $request)
	{
		$bet_log = Bet_log::where([
				['user_id', auth('user')->user()->id],
				['schedule_id', $request->schedule_id],
				['fight_id', $request->fight_id]
			])->get()->toArray();
		
		$meron = [
			'bet_amount' => 0,
			'result_amount' => 0
		];
		$wala = [
			'bet_amount' => 0,
			'result_amount' => 0
		];
		$draw = [
			'bet_amount' => 0,
			'result_amount' => 0
		];	

		for($i=0; count($bet_log) > $i; $i++){
			if($bet_log[$i]['bet_select'] == 1) {
				$meron['bet_amount'] = $meron['bet_amount'] + $bet_log[$i]['bet_amount'];
				$meron['result_amount'] = $meron['result_amount'] + $bet_log[$i]['result_amount'];
			} else if($bet_log[$i]['bet_select'] == 2) {
				$wala['bet_amount'] = $wala['bet_amount'] + $bet_log[$i]['bet_amount'];
				$wala['result_amount'] = $wala['result_amount'] + $bet_log[$i]['result_amount'];
			} else {
				$draw['bet_amount'] = $draw['bet_amount'] + $bet_log[$i]['bet_amount'];
				$draw['result_amount'] = $draw['result_amount'] + $bet_log[$i]['result_amount'];
			}
		}

		$status = 0;

		if($bet_log) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'controller' => 'BetLogController',
				'function' => 'userBet'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $bet_log,
			'meron' => $meron,
			'wala' => $wala,
			'draw' => $draw,
			'status' => $status
		]);
	}

    public function scan(Request $request)
	{
		$validator = $this->validator($request, 'scan');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

		$bet_log = Bet_log::with([
							'fight' => function ($query) {
								$query->select('id', 'fight_no', 'meron_payout', 'wala_payout');
							}, 
							'schedule' => function ($query) {
								$query->select('id', 'draw_rake', 'event_name', 'datetime');
							}
						])->where('barcode', $request->barcode)->first();
						
		$status = 0;

		if($bet_log) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $bet_log->id,
				'controller' => 'BetLogController',
				'function' => 'Scan'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $bet_log,
			'status' => $status
		]);
	}

    public function claim(Request $request)
	{
		$validator = $this->validator($request, 'claim');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}
		
		$status = 0;

		$bet_log = Bet_log::with([
								'fight' => function ($query) {
									$query->select('id', 'fight_no', 'meron_payout', 'wala_payout');
								}, 
								'schedule' => function ($query) {
									$query->select('id', 'draw_rake', 'event_name', 'datetime');
								}
							])->where('barcode', $request->barcode)->first();

		if($bet_log->status === 0 || $bet_log->fight->claim_status === 0) {
			return response()->json([
				'message' => 'Claiming is not available',
				'status' => $status
			]);
		} else if($bet_log->claimed === 1) {
			return response()->json([
				'message' => 'bet is already claimed',
				'status' => $status
			]);
		} else {
			$status = $bet_log->status;
			
			if($status !== 0) {
				Bet_log::where('barcode', $request->barcode)->update(['claimed' => 1]);
				$bet_log['claimed'] = 1;
			}

			switch ($status) {
				case 0:
					break;
				case 1:
					User::where('id', $bet_log->user_id)->update([
						'money' => DB::raw('(CASE status WHEN 1 THEN money - ' . $bet_log->result_amount . ' ' . 'END)')
					]);
					break;
				case 2:
					break;
				case 3:
					if($bet_log->bet_select === 3) {
						User::where('id', $bet_log->user_id)->update([
							'money' => DB::raw('(CASE status WHEN 1 THEN money - ' . $bet_log->result_amount . ' ' . 'END)')
						]);
					} else {
						User::where('id', $bet_log->user_id)->update([
							'money' => DB::raw('(CASE status WHEN 1 THEN money - ' . $bet_log->bet_amount . ' ' . 'END)')
						]);
					}
					break;
				case 4:
					User::where('id', $bet_log->user_id)->update([
						'money' => DB::raw('(CASE status WHEN 1 THEN money - ' . $bet_log->bet_amount . ' ' . 'END)')
					]);
					break;
			}

			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $bet_log->id,
				'controller' => 'BetLogController',
				'function' => 'Claim'
			]);

			$status = 1; 
		}

        return response()->json([
			'data' => $bet_log,
			'status' => $status
		]);
	}

	public function list(Request $request)
	{
		$orWhere_columns = [
            'bet_log.bet_amount',
            'bet_log.result',
            'bet_log.barcode'
        ];

        $key = ($request->search_key) ? $request->search_key : '';

        if($request->search_key){
            $key = $request->search_key;
        }

        $limit = ($request->limit) ? $request->limit : 50;
        $sort_column = ($request->sort_column) ? $request->sort_column : 'created_at';
        $sort_order = ($request->sort_order) ? $request->sort_order : 'desc';

		$bet_log = Bet_log::with(['fight'])->where(function ($q) use ($orWhere_columns, $key) {
                            foreach ($orWhere_columns as $column) {
                                $q->orWhere($column, 'LIKE', "%{$key}%");
                            }
                        });

        if($request->from && $request->to){
            $bet_log = $bet_log->whereBetween('created_at', [Carbon::parse($request->from)->format('Y-m-d H:i:s'), Carbon::parse($request->to)->format('Y-m-d H:i:s')]);
        }

        $bet_log = $bet_log->orderBy($sort_column, $sort_order)->paginate($limit);
		
		$this->action_log->create([
			'user_id' => auth('user')->user()->id,
			'controller' => 'BetLogController',
			'function' => 'List'
		]);

        return response()->json([
			'data' => $bet_log,
			'status' => 1
		]);
	}

	public function create(Request $request)
	{   
		$validator = $this->validator($request, 'create');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}
		if(auth('user')->user()->user_type_id != 5) {
			User::where('id', auth('user')->user()->id)->update([
				'money' => auth('user')->user()->money + $request->bet_amount
			]);
		} else {
			if(auth('user')->user()->money >= $request->bet_amount){
				User::where('id', auth('user')->user()->id)->update([
					'money' => auth('user')->user()->money - $request->bet_amount
				]);
			} else {
				return response()->json([
					'message' => 'not enough money',
					'status' => 0
				]);
			}
		}
        $request['barcode'] = $this->barcodeNumber($request->schedule_id, $request->fight_id);
        $request['user_id'] = auth('user')->user()->id;
		
		$bet_log = Bet_log::create($request->all());

		$status = 0;

		if($bet_log) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $bet_log->id,
				'controller' => 'BetLogController',
				'function' => 'Create'
			]);

			$status = 1;
		}

        return response()->json([
			'barcode' => $request->barcode,
			'status' => $status
		]);
	}

	public function edit(Request $request)
	{
		$validator = $this->validator($request, 'edit');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

		$bet_log = Bet_log::find($request->id);
		
		$status = 0;

		if($bet_log) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $bet_log->id,
				'controller' => 'BetLogController',
				'function' => 'Edit'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $bet_log,
			'status' => $status
		]);
	}

	public function update(Request $request)
	{

		$validator = $this->validator($request, 'update');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

        $bet_log = Bet_log::where('id', $request->id)->update($request->all());
		
		$status = 0;

		if($bet_log > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'BetLogController',
				'function' => 'Update'
			]);

			$status = 1;
		}

        return response()->json([
			'status' => $status
		]);
		
	}

	public function updateStatus(Request $request)
	{

		$validator = $this->validator($request, 'update-status');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

		$update = [
			'status' => $request->status
        ];

        $bet_log = Bet_log::where('id', $request->id)->update($update);
		
		$status = 0;

		if($bet_log > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'BetLogController',
				'function' => 'UpdateStatus'
			]);

			$status = 1;
		}

        return response()->json([
			'status' => $status
		]);
		
	}

	public function delete(Request $request)
	{
		$validator = $this->validator($request, 'delete');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

		$bet_log = Bet_log::where('id', $request->id)->delete();
		
		$status = 0;

		if($bet_log > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $bet_log->id,
				'controller' => 'BetLogController',
				'function' => 'Delete'
			]);

			$status = 1;
		}

        return response()->json([
			'status' => $status
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
        if($x == 'create') {
        	$rules = [
	            'schedule_id' => 'required|integer',
	            'fight_id' => 'required|integer',
	            'bet_select' => 'required|integer',
	            'status' => 'integer',
                'bet_amount' => 'required'
	        ];
        }
        else if($x == 'update') {
        	$rules = [
        		'id' => 'required|integer',
	        ];
        }
        else if($x == 'update-status') {
        	$rules = [
        		'id' => 'required|integer',
	            'status' => 'required|integer',
	        ];
        }
        else if($x == 'scan' || $x == 'claim') {
        	$rules = [
	            'barcode' => 'required|integer'
	        ];
        }
        else if($x == 'get' || $x == 'edit' || $x == 'delete') {
        	$rules = [
	            'id' => 'required|integer'
	        ];
        }

        $validate =  Validator::make($request->all(), $rules, $messages);

		return $validate;
    }

    private function barcodeNumber($schedule_id, $fight_id) {

		$total_bet = Bet_log::where('schedule_id', $schedule_id)->count();
        $datetime = Carbon::now()->format('md');
		$number = $datetime . rand(10,99) . '0' . $total_bet + 1 . '0' . $fight_id;
		
        if ($this->barcodeExists($number)) {
            return $this->barcodeNumber($schedule_id, $fight_id);
        }
    
        return $number;
    }

    private function barcodeExists($number) {
        return Bet_log::whereBarcode($number)->exists();
    }
}
