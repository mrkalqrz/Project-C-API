<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Action_log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class GroupController extends Controller
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

		$group = Group::with(['arena'])->find($request->id);

		$status = 0;

		if($group) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $group->id,
				'controller' => 'GroupController',
				'function' => 'Get'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $group,
			'status' => $status
		]);
	}

	public function list(Request $request)
	{
		$orWhere_columns = [
            'group.name',
            'group.owner',
            'group.address',
            'group.description'
        ];

        $key = ($request->search_key) ? $request->search_key : '';

        if($request->search_key){
            $key = $request->search_key;
        }

        $limit = ($request->limit) ? $request->limit : 50;
        $sort_column = ($request->sort_column) ? $request->sort_column : 'created_at';
        $sort_order = ($request->sort_order) ? $request->sort_order : 'desc';

		$group = Group::with(['arena'])->where(function ($q) use ($orWhere_columns, $key) {
                            foreach ($orWhere_columns as $column) {
                                $q->orWhere($column, 'LIKE', "%{$key}%");
                            }
                        });

        if($request->from && $request->to){
            $group = $group->whereBetween('created_at', [Carbon::parse($request->from)->format('Y-m-d H:i:s'), Carbon::parse($request->to)->format('Y-m-d H:i:s')]);
        }

        $group = $group->orderBy($sort_column, $sort_order)->paginate($limit);

		$this->action_log->create([
			'user_id' => auth('user')->user()->id,
			'controller' => 'GroupController',
			'function' => 'List'
		]);

        return response()->json([
			'data' => $group,
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

		$group = Group::create($request->all());
		
		$status = 0;

		if($group) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $group->id,
				'controller' => 'GroupController',
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

		$group = Group::find($request->id);

		$status = 0;

		if($group) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $group->id,
				'controller' => 'GroupController',
				'function' => 'Edit'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $group,
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

        $group = Group::where('id', $request->id)->update($request->all());

		$status = 0;

		if($group > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'GroupController',
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

        $group = Group::where('id', $request->id)->update($update);

		$status = 0;

		if($group > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'GroupController',
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

		$group = Group::where('id', $request->id)->delete();

		$status = 0;

		if($group > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'GroupController',
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
	            'name' => 'required|unique:group',
	            'status' => 'integer'
	        ];
        }
        else if($x == 'update') {
        	$rules = [
        		'id' => 'required|integer',
	            'name' => 'unique:group,name,' . $request->id,
	            'status' => 'integer',
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
