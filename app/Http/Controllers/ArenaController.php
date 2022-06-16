<?php

namespace App\Http\Controllers;

use App\Models\Arena;
use App\Models\Action_log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ArenaController extends Controller
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

		$arena = Arena::find($request->id);

		$status = 0;

		if($arena) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $arena->id,
				'controller' => 'ArenaController',
				'function' => 'Get'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $arena,
			'status' => $status
		]);
	}

	public function list(Request $request)
	{
		$orWhere_columns = [
            'arena.name',
        ];

        $key = ($request->search_key) ? $request->search_key : '';

        if($request->search_key){
            $key = $request->search_key;
        }

        $limit = ($request->limit) ? $request->limit : 50;
        $sort_column = ($request->sort_column) ? $request->sort_column : 'created_at';
        $sort_order = ($request->sort_order) ? $request->sort_order : 'desc';

		$arena = Arena::where(function ($q) use ($orWhere_columns, $key) {
                            foreach ($orWhere_columns as $column) {
                                $q->orWhere($column, 'LIKE', "%{$key}%");
                            }
                        });

        if($request->from && $request->to){
            $arena = $arena->whereBetween('created_at', [Carbon::parse($request->from)->format('Y-m-d H:i:s'), Carbon::parse($request->to)->format('Y-m-d H:i:s')]);
        }

        $arena = $arena->orderBy($sort_column, $sort_order)->paginate($limit);
		
		$this->action_log->create([
			'user_id' => auth('user')->user()->id,
			'controller' => 'ArenaController',
			'function' => 'List'
		]);

        return response()->json([
			'data' => $arena,
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

		$arena = Arena::create($request->all());
		
		$status = 0;
		
		if($arena) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $arena->id,
				'controller' => 'ArenaController',
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

		$arena = Arena::find($request->id);
		
		$status = 0;
		
		if($arena) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $arena->id,
				'controller' => 'ArenaController',
				'function' => 'Edit'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $arena,
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

        $arena = Arena::where('id', $request->id)->update($request->all());

		$status = 0;
		
		if($arena > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'ArenaController',
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

		$arena = Arena::where('id', $request->id)->delete();
		
		$status = 0;
		
		if($arena > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'ArenaController',
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
	            'name' => 'required|unique:arena',
	            'user_id' => 'required|integer',
	            'owner_name' => 'required',
	            'status' => 'integer'
	        ];
        }
        else if($x == 'update') {
        	$rules = [
        		'id' => 'required|integer',
	            'name' => 'unique:arena,name,' . $request->id,
	            'status' => 'integer',
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
