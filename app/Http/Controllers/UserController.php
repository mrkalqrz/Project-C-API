<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Action_log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Events\UserStatus;

class UserController extends Controller
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

		$user = User::with(['group'])->find($request->id);

		$status = 0;

		if($user) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $user->id,
				'controller' => 'UserController',
				'function' => 'Get'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $user,
			'status' => $status
		]);
	}

	public function list(Request $request)
	{
		$orWhere_columns = [
            'user.username',
            'user.firstname',
            'user.lastname',
            'user.email',
            'user.phone'
        ];

        $key = ($request->search_key) ? $request->search_key : '';

        if($request->search_key){
            $key = $request->search_key;
        }

        $limit = ($request->limit) ? $request->limit : 50;
        $sort_column = ($request->sort_column) ? $request->sort_column : 'created_at';
        $sort_order = ($request->sort_order) ? $request->sort_order : 'desc';

		$user = User::where(function ($q) use ($orWhere_columns, $key) {
                            foreach ($orWhere_columns as $column) {
                                $q->orWhere($column, 'LIKE', "%{$key}%");
                            }
                        });

        if($request->status){
            $user = $user->where('status', $request->status);
        }

        if($request->user_type_id){
            $user = $user->where('user_type_id', $request->user_type_id);
        }

        if($request->from && $request->to){
            $user = $user->whereBetween('created_at', [Carbon::parse($request->from)->format('Y-m-d H:i:s'), Carbon::parse($request->to)->format('Y-m-d H:i:s')]);
        }

        $user = $user->orderBy($sort_column, $sort_order)->paginate($limit);

		$this->action_log->create([
			'user_id' => auth('user')->user()->id,
			'controller' => 'UserController',
			'function' => 'List'
		]);

        return response()->json([
			'data' => $user,
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

        $request['password'] = Hash::make($request->password);

		$user = User::create($request->all());
		
		$status = 0;

		if($user) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $user->id,
				'controller' => 'UserController',
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

		$user = User::find($request->id);

		$status = 0;

		if($user) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $user->id,
				'controller' => 'UserController',
				'function' => 'Edit'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $user,
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

        $user = User::where('id', $request->id)->update($request->all());

		$status = 0;

		if($user > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'UserController',
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

        $user = User::where('id', $request->id)->update($update);

		$status = 0;

		if($user > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'UserController',
				'function' => 'UpdateStatus'
			]);

			broadcast( 
				new UserStatus(
					response()->json([
						'data' => [
							'user_id' => $request->id,
							'status' => $request->status
						],
					])
				)
			);

			$status = 1;
		}

        return response()->json([
			'status' => $status
		]);
		
	}

	public function updatePassword(Request $request)
	{

		$validator = $this->validator($request, 'update-password');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

		$update = [
			'password' => Hash::make($request->password)
        ];

        $user = User::where('id', $request->id)->update($update);

		$status = 0;

		if($user > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'UserController',
				'function' => 'UpdatePassword'
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

		$user = User::where('id', $request->id)->delete();

		$status = 0;

		if($user > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'UserController',
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
	            'username' => 'required|unique:user',
	            'password' => 'required|between:5,255|confirmed',
	            'user_type_id' => 'required|integer',
	            'group_id' => 'required|integer',
	            'pin' => 'digits:6',
	        ];
        }
        else if($x == 'update') {
        	$rules = [
        		'id' => 'required|integer',
	            'username' => 'unique:user,username,' . $request->id,
	            'status' => 'integer',
	        ];
        }
        else if($x == 'update-status') {
        	$rules = [
        		'id' => 'required|integer',
	            'status' => 'required|integer',
	        ];
        }
        else if($x == 'update-password') {
        	$rules = [
        		'id' => 'required|integer',
	            'password' => 'required|between:5,255|confirmed',
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
