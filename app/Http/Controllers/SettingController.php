<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Action_log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct()
    {
		$this->action_log = new Action_log;
    }

	public function list(Request $request)
	{
		$orWhere_columns = [
			'setting.type',
			'setting.name'
        ];

        $key = ($request->search_key) ? $request->search_key : '';

        if($request->search_key){
            $key = $request->search_key;
        }

        $limit = ($request->limit) ? $request->limit : 50;
        $sort_column = ($request->sort_column) ? $request->sort_column : 'created_at';
        $sort_order = ($request->sort_order) ? $request->sort_order : 'desc';

		$setting = Setting::where(function ($q) use ($orWhere_columns, $key) {
                            foreach ($orWhere_columns as $column) {
                                $q->orWhere($column, 'LIKE', "%{$key}%");
                            }
                        });
	
        if($request->from && $request->to){
            $setting = $setting->whereBetween('created_at', [Carbon::parse($request->from)->format('Y-m-d H:i:s'), Carbon::parse($request->to)->format('Y-m-d H:i:s')]);
        }

        $setting = $setting->orderBy($sort_column, $sort_order)->paginate($limit);
		
		$this->action_log->create([
			'user_id' => auth('user')->user()->id,
			'controller' => 'SettingsController',
			'function' => 'List'
		]);

        return response()->json([
			'data' => $setting,
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

		$setting = Setting::create($request->all());
		
		$status = 0;

		if($setting) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $setting->id,
				'controller' => 'SettingController',
				'function' => 'Create'
			]);

			$status = 1;
		}

        return response()->json([
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
        
        $setting = Setting::where('id', $request->id)->update($request->all());

		$status = 0;

		if($setting > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'SettingController',
				'function' => 'Update',
                'note' => json_encode($request->all())
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
	            'type' => 'required',
	            'name' => 'required'
	        ];
        }
        else if($x == 'update') {
        	$rules = [
        		'id' => 'required|integer',
	            'status' => 'integer',
	        ];
        }

        $validate =  Validator::make($request->all(), $rules, $messages);

		return $validate;
    }
}
