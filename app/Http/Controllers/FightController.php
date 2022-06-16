<?php

namespace App\Http\Controllers;

use App\Models\Fight;
use App\Models\Bet_log;
use App\Models\Action_log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Events\FightStatus;
use App\Events\FightAnnouncement;
use App\Events\FightTotalBet;
use App\Events\FightCurrent;


class FightController extends Controller
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

		$fight = Fight::with(['arena','schedule'])->find($request->id);

		$status = 0;

		if($fight) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $fight->id,
				'controller' => 'FightController',
				'function' => 'Get'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $fight,
			'status' => $status
		]);
	}
	
	public function getCurrent(Request $request)
	{
		$validator = $this->validator($request, 'get-current');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

		$fight = Fight::where([
			['schedule_id', $request->schedule_id],
			['status', '!=', 2]
		])->orderBy('fight_no', 'asc')->take(2)->get();
		
		$status = 0;

		if(count($fight) > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $fight[0]->id,
				'controller' => 'FightController',
				'function' => 'GetCurrent'
			]);
		
			broadcast( 
				new FightCurrent(
					response()->json([
						'data' => $fight[0],
					])
				)
			);

			$status = 1;
		}

        return response()->json([
			'data' => $fight[0],
			'next_fight_id' => $fight[1]->id,
			'status' => $status
		]);
	}

	public function list(Request $request)
	{
		$orWhere_columns = [
            'fight.fight_no',
        ];

        $key = ($request->search_key) ? $request->search_key : '';

        if($request->search_key){
            $key = $request->search_key;
        }

        $limit = ($request->limit) ? $request->limit : 50;
        $sort_column = ($request->sort_column) ? $request->sort_column : 'created_at';
        $sort_order = ($request->sort_order) ? $request->sort_order : 'desc';

		$fight = Fight::with(['arena','schedule'])->where(function ($q) use ($orWhere_columns, $key) {
                            foreach ($orWhere_columns as $column) {
                                $q->orWhere($column, 'LIKE', "%{$key}%");
                            }
                        });
		
		if($request->status) {
			$fight = $fight->where('status', $request->status);
		}

		if($request->schedule_id) {
			$fight = $fight->where('schedule_id', $request->schedule_id);
		}

        if($request->from && $request->to){
            $fight = $fight->whereBetween('created_at', [Carbon::parse($request->from)->format('Y-m-d H:i:s'), Carbon::parse($request->to)->format('Y-m-d H:i:s')]);
        }

        $fight = $fight->orderBy($sort_column, $sort_order)->paginate($limit);
		
		$this->action_log->create([
			'user_id' => auth('user')->user()->id,
			'controller' => 'FightController',
			'function' => 'List'
		]);

        return response()->json([
			'data' => $fight,
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
		
        if($this->fightExist($request)){
			return response()->json([
				'status' => 0
			]);
		}

		$fight = Fight::create($request->all());
		
		$status = 0;

		if($fight) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $fight->id,
				'controller' => 'FightController',
				'function' => 'Create'
			]);

			$status = 1;
		}

        return response()->json([
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

		$fight = Fight::find($request->id);

		$status = 0;

		if($fight) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $fight->id,
				'controller' => 'FightController',
				'function' => 'Edit'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $fight,
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

        $fight = Fight::where('id', $request->id)->update($request->all());

		$status = 0;

		if($fight > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'FightController',
				'function' => 'Update'
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

		$fight = Fight::where('id', $request->id)->delete();

		$status = 0;

		if($fight) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'FightController',
				'function' => 'Delete'
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

        $fight = Fight::where('id', $request->id)->update($update);

		$status = 0;

		if($fight > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'FightController',
				'function' => 'UpdateStatus'
			]);	
		
			broadcast( 
				new FightStatus(
					response()->json([
						'fight_id' => $request->id,
						'status' => $request->status
					])
				)
			);

			$status = 1;
		}

        return response()->json([
			'status' => $status
		]);
	}

	public function updateBet(Request $request)
	{

		$validator = $this->validator($request, 'update-bet');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}
		
		$bet_log = Bet_log::where('fight_id', $request->id)
								->groupBy('bet_select')
								->selectRaw('bet_select, COALESCE(sum(bet_amount),6) as total_bet')
								->get();

		$fight_rake = Fight::where('id', $request->id)
								->pluck('rake_percentage')
								->first();
		$meron_bet = 0;
		$wala_bet = 0;
		$draw_bet = 0;
		$meron_payout = 0;
		$wala_payout = 0;

		for($i=0; count($bet_log) > $i; $i++){
			$bet_select=$bet_log[$i]->bet_select;
			switch ($bet_select) {
				case 1:
					$meron_bet = $bet_log[$i]->total_bet;
					break;
				case 2:
					$wala_bet = $bet_log[$i]->total_bet;
					break;
				case 3:
					$draw_bet = $bet_log[$i]->total_bet;
					break;
			}
		}

		$total_bet = $meron_bet + $wala_bet;
		$rake = $fight_rake / 100;
		$owner_profit = $total_bet * $rake;
		$base_total = $total_bet - $owner_profit;
		if($meron_bet != 0) { $meron_payout = number_format(($base_total / $meron_bet) * 100, 2); }
		if($wala_bet != 0) { $wala_payout = number_format(($base_total / $wala_bet) * 100, 2); }

		$update = [
			'meron_bet' => $meron_bet,
			'wala_bet' => $wala_bet,
			'draw_bet' => $draw_bet,
			'meron_payout' => $meron_payout,
			'wala_payout' => $wala_payout,
			'total_amount' => $total_bet
		];

        $fight = Fight::where('id', $request->id)->update($update);
		
		$status = 0;

		if($fight > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'FightController',
				'function' => 'UpdateBet'
			]);
		
			broadcast( 
				new FightTotalBet(
					response()->json([
						'fight_id' => $request->id,
						'data' => $update
					])
				)
			);

			$status = 1;
		}

        return response()->json([
			'status' => $status
		]);
		
	}
	
    public function updateResult(Request $request)
	{
		$validator = $this->validator($request, 'update-result');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

		$fight_data = Fight::with(['schedule'])->where('id', $request->id)->first();

		$total_commision = 0;
		$draw_commission = 0;
		$total_bet = $fight_data->meron_bet + $fight_data->wala_bet;
		$rake = $fight_data->rake_percentage / 100;
		// $owner_profit = $total_bet * $rake;
		
		$result = $request->result;
		switch ($result) {
			case 1:
			case 2:
				$total_win = Bet_log::select(DB::raw('IFNULL(SUM(result_amount), 0) AS `total`'))
				->where('schedule_id', $fight_data->schedule_id)
				->where('fight_id', $request->id)
				->where('status', 1)
				->first()->total;

				$total_commision = $total_bet - $total_win;
				$draw_commission = $fight_data->draw_bet;
				break;
			case 3:
				$draw_commission = $fight_data->draw_bet - ($fight_data->draw_bet * $fight_data->schedule->draw_rake);
				break;
			case 4:
				break;
		}

		$update = [
			'result' => $result,
			'total_commission' => $total_commision,
			'draw_commission' => $draw_commission,
			'status' => 2
        ];

        $fight = Fight::where('id', $request->id)->update($update);
		
		$update_bet = [
			'result_amount' => DB::raw('(CASE bet_select 
			WHEN 1 THEN bet_amount * ' . number_format($fight_data->meron_payout / 100, 2) . '
			WHEN 2 THEN bet_amount * ' . number_format($fight_data->wala_payout / 100, 2) . '
			WHEN 3 THEN bet_amount * ' . $fight_data->schedule->draw_rake . '
			END)'),
			'status' => DB::raw('(CASE WHEN bet_select = '. $result .' THEN 1 ELSE 2 END)'),
			'result' => $result
		];
		
		$bet_log = Bet_log::where('fight_id', $request->id)->update($update_bet);
		
		$status = 0;

		if($fight > 0 && $bet_log > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'FightController',
				'function' => 'UpdateResult'
			]);

			broadcast( 
				new FightStatus(
					response()->json([
					'fight_id' => $request->id,
					'status' => 2
					])
				)
			);

			$status = 1;
		}

        return response()->json([
			'status' => $status
		]);
	}

    public function updateAnnouncement(Request $request)
	{
		$validator = $this->validator($request, 'update-announcement');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

        $fight = Fight::where('id', $request->id)->update($request->all());

		$status = 0;

		if($fight > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'FightController',
				'function' => 'UpdateStatus'
			]);	
		
			broadcast( 
				new FightAnnouncement(
					response()->json([
					'fight_id' => $request->id,
					'announcement' => $request->announcement
					])
				)
			);

			$status = 1;
		}

        return response()->json([
			'status' => $status
		]);
	}

    public function regrade(Request $request)
	{
		$validator = $this->validator($request, 'regrade');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

		$update = [
			'regrade_count' => $request->regrade_count + 1
        ];

        $fight = Fight::where('id', $request->id)->update($update);

		$status = 0;

		if($fight > 0) {
			$this->updateResult($request);
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'FightController',
				'function' => 'Regrade'
			]);	
		
			broadcast( 
				new FightStatus(
					response()->json([
						'fight_id' => $request->id,
						'status' => $request->status
					])
				)
			);

			$status = 1;
		}

        return response()->json([
			'status' => $status
		]);
	}

    public function updateClaimStatus(Request $request)
	{
		$validator = $this->validator($request, 'update-claim-status');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

		$update = [
			'claim_status' => $request->claim_status
        ];

        $fight = Fight::where('id', $request->id)->update($update);

		$status = 0;

		if($fight > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'FightController',
				'function' => 'UpdateClaimStatus'
			]);	
		
			broadcast( 
				new FightStatus(
					response()->json([
						'fight_id' => $request->id,
						'status' => $request->status
					])
				)
			);

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
	            'arena_id' => 'required|integer',
	            'schedule_id' => 'required|integer',
	            'admin_id' => 'required|integer',
	            'fight_no' => 'required|integer'
	        ];
        }
        else if($x == 'update' || $x == 'update-bet') {
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
        else if($x == 'update-claim-status') {
        	$rules = [
        		'id' => 'required|integer',
	            'claim_status' => 'required|integer',
	        ];
        }
        else if($x == 'update-result') {
        	$rules = [
        		'id' => 'required|integer',
	            'result' => 'required|integer',
	        ];
        }
        else if($x == 'update-announcement') {
        	$rules = [
        		'id' => 'required|integer'
	        ];
        }
        else if($x == 'regrade') {
        	$rules = [
        		'id' => 'required|integer',
	            'regrade_count' => 'required|integer',
	        ];
        }
        else if($x == 'get' || $x == 'edit' || $x == 'delete') {
        	$rules = [
	            'id' => 'required|integer'
	        ];
        }
        else if($x == 'get-current') {
        	$rules = [
	            'schedule_id' => 'required|integer'
	        ];
        }

        $validate =  Validator::make($request->all(), $rules, $messages);

		return $validate;
    }

	private function fightExist(Request $request){
		return Fight::where('schedule_id', $request->schedule_id)->where('fight_no', $request->fight_no)->exists();
	}
}
