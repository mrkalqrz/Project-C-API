<?php

namespace App\Http\Controllers;

use App\Models\User_type;
use App\Models\Action_log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class UserTypeController extends Controller
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

		$user_type = User_type::find($request->id);

		$status = 0;

		if($user_type) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $user_type->id,
				'controller' => 'UserTypeController',
				'function' => 'Get'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $user_type,
			'status' => $status
		]);
	}

	public function list(Request $request)
	{
		$orWhere_columns = [
			'user_type.name'
        ];

        $key = ($request->search_key) ? $request->search_key : '';

        if($request->search_key){
            $key = $request->search_key;
        }

        $limit = ($request->limit) ? $request->limit : 50;
        $sort_column = ($request->sort_column) ? $request->sort_column : 'created_at';
        $sort_order = ($request->sort_order) ? $request->sort_order : 'desc';

		$user_type = User_type::where(function ($q) use ($orWhere_columns, $key) {
                            foreach ($orWhere_columns as $column) {
                                $q->orWhere($column, 'LIKE', "%{$key}%");
                            }
                        });
	
        if($request->from && $request->to){
            $user_type = $user_type->whereBetween('created_at', [Carbon::parse($request->from)->format('Y-m-d H:i:s'), Carbon::parse($request->to)->format('Y-m-d H:i:s')]);
        }

        $user_type = $user_type->orderBy($sort_column, $sort_order)->paginate($limit);
		
		$this->action_log->create([
			'user_id' => auth('user')->user()->id,
			'controller' => 'UserTypeController',
			'function' => 'List'
		]);

        return response()->json([
			'data' => $user_type,
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

		$user_type = User_type::create($request->all());
		
		$status = 0;

		if($user_type) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $user_type->id,
				'controller' => 'UserTypeController',
				'function' => 'Create'
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
	            'name' => 'required|unique:user_type',
	        ];
        }
        else if($x == 'get') {
        	$rules = [
	            'id' => 'required|integer'
	        ];
        }

        $validate =  Validator::make($request->all(), $rules, $messages);

		return $validate;
    }
}
