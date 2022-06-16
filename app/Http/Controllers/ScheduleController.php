<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Models\Fight;
use App\Models\Action_log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Events\ScheduleStatus;

class ScheduleController extends Controller
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

		$schedule = Schedule::with(['arena','user'])->find($request->id);

		$status = 0;

		if($schedule) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $schedule->id,
				'controller' => 'ScheduleController',
				'function' => 'Get'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $schedule,
			'status' => $status
		]);
	}
	
	public function getCurrent(Request $request)
	{
		$schedule = Schedule::with(['arena'])->where('status', 1)->orderBy('id', 'asc')->first();

		$status = 0;

		if($schedule) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $schedule->id,
				'controller' => 'ScheduleController',
				'function' => 'GetCurrent'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $schedule,
			'status' => $status
		]);
	}

	public function list(Request $request)
	{
		$orWhere_columns = [
            'schedule.event_name',
            'schedule.rake_percentage',
            'schedule.total_fights'
        ];

        $key = ($request->search_key) ? $request->search_key : '';

        if($request->search_key){
            $key = $request->search_key;
        }

        $limit = ($request->limit) ? $request->limit : 50;
        $sort_column = ($request->sort_column) ? $request->sort_column : 'created_at';
        $sort_order = ($request->sort_order) ? $request->sort_order : 'desc';

		$schedule = Schedule::with(['arena','user'])->where(function ($q) use ($orWhere_columns, $key) {
                            foreach ($orWhere_columns as $column) {
                                $q->orWhere($column, 'LIKE', "%{$key}%");
                            }
                        });

        if($request->from && $request->to){
            $schedule = $schedule->whereBetween('created_at', [Carbon::parse($request->from)->format('Y-m-d H:i:s'), Carbon::parse($request->to)->format('Y-m-d H:i:s')]);
        }

        $schedule = $schedule->orderBy($sort_column, $sort_order)->paginate($limit);
		
		$this->action_log->create([
			'user_id' => auth('user')->user()->id,
			'controller' => 'ScheduleController',
			'function' => 'List'
		]);

        return response()->json([
			'data' => $schedule,
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

        $request['user_id'] = auth('user')->user()->id;
		$admin_id = $request->admin_id;
		unset($request['admin_id']);

		$schedule = Schedule::create($request->all());

		if($schedule) {
			$fight_data = [];
			for($i=1; $schedule->total_fights >= $i; $i++){
				$data = [];
				$data['arena_id'] = $schedule->arena_id;
				$data['admin_id'] = $admin_id;
				$data['schedule_id'] = $schedule->id;
				$data['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
				if($request->rake_percentage) {
					$data['rake_percentage'] = $request->rake_percentage;
				}
				$data['fight_no'] = $i;
				$fight_data[] = $data;
			}

			$insert_fight = Fight::insert($fight_data);

			$status = 0;

			if($insert_fight) {
				$this->action_log->create([
					'user_id' => auth('user')->user()->id,
					'_id' => $schedule->id,
					'controller' => 'ScheduleController',
					'function' => 'Create'
				]);

				$status = 1;
			}

			if(!$status) {
				$schedule = Schedule::where('id', $schedule->id)->delete();
			}
			
			return response()->json([
				'status' => $status
			]);
		}

        return response()->json([
			'status' => 0
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

		$schedule = Schedule::find($request->id);

		$status = 0;

		if($schedule) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $schedule->id,
				'controller' => 'ScheduleController',
				'function' => 'Edit'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $schedule,
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

        $schedule = Schedule::where('id', $request->id)->update($request->all());

		$status = 0;

		if($schedule) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'ScheduleController',
				'function' => 'Update'
			]);

			$status = 1;
		}

		if($request->total_fights && $schedule){

			$fight = Fight::where('schedule_id', $request->id)->orderBy('fight_no', 'desc')->first();

			if($request->total_fights > $fight->fight_no){
				$fight_data = [];
				$fight->fight_no++;
				for($fight->fight_no; $request->total_fights >= $fight->fight_no; $fight->fight_no++){
					$data = [];
					$data['arena_id'] = $fight->arena_id;
					$data['admin_id'] = $fight->admin_id;
					$data['schedule_id'] = $fight->schedule_id;
					$data['created_at'] = Carbon::now()->format('Y-m-d H:i:s');
					if($request->rake_percentage) {
						$data['rake_percentage'] = $request->rake_percentage;
					}
					$data['fight_no'] = $fight->fight_no;
					$fight_data[] = $data;
				}

				$insert_fight = Fight::insert($fight_data);

				$status = ($insert_fight > 0) ? 1 : 0;
			} 
			else if ($request->total_fights < $fight->fight_no) {
				$to_subtract = $fight->fight_no - $request->total_fights;
				$fight = Fight::where('schedule_id', $request->id)->take($to_subtract)->orderBy('fight_no', 'desc')->pluck('id')->toArray();
				
				$delete_fight = Fight::whereIn('id', $fight)->delete();

				$status = ($delete_fight > 0) ? 1 : 0;
			}

		} 

		if($request->rake_percentage && $schedule){

			$update_fight = Fight::where('schedule_id', $request->id)->update(['rake_percentage' => $request->rake_percentage]);
			$status = ($update_fight > 0) ? 1 : 0;
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

        $schedule = Schedule::where('id', $request->id)->update($update);
		$status = 0;

		if($schedule > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'ScheduleController',
				'function' => 'UpdateStatus'
			]);

			broadcast( 
				new ScheduleStatus(
					response()->json([
						'schedule_id' => $request->id,
						'status' => $request->status,
					])
				)
			);

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

		$schedule = Schedule::where('id', $request->id)->delete();

		$status = 0;

		if($schedule > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'ScheduleController',
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
	            'arena_id' => 'required|integer',
	            'admin_id' => 'required|integer',
	            'event_name' => 'required',
	            'total_fights' => 'required|integer',
	            'status' => 'integer'
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
        else if($x == 'get' || $x == 'edit' || $x == 'delete') {
        	$rules = [
	            'id' => 'required|integer'
	        ];
        }

        $validate =  Validator::make($request->all(), $rules, $messages);

		return $validate;
    }
}
