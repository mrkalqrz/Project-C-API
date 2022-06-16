<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Action_log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected $user;

    public function __construct()
    {
		$this->user = new User;
		$this->action_log = new Action_log;
    }

	public function login(Request $request)
	{
		$credentials = $request->only(['username', 'password']);
		$user = User::where('username', $request->username)->first();
		
		if($user === null || $user->status !== 1) {
			return response()->json([
				'status' => 0
			]);
		}
		
		$token = auth('user')->attempt($credentials);
		
		if ($token) {

			User::where('username', $request->username)->update(['last_login' => Carbon::now()->format('Y-m-d H:i:s')]);

			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'controller' => 'AuthController',
				'function' => 'Login'
			]);
			return [
				'token' => $token,
				'status' => 1
			];
		} else {
			$this->action_log->create([
				'controller' => 'AuthController',
				'function' => 'Login',
				'note' => 'Failed to login, username: ' . $request->username,
			]);
			return ['status' => 0];
		}
	}

    public function register(Request $request)
	{
		$validator = $this->validator($request, 'register');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}

		$request['password'] = Hash::make($request->password);

		$user = $this->user->create($request->all());
		$status = 0;
		if($user) {

			$this->action_log->create([
				'_id' => $user->id,
				'controller' => 'AuthController',
				'function' => 'Register'
			]);

			$status = 1;
		}

        return response()->json([
			'status' => $status
		]);
	}

    public function profile()
    {
		$user = auth('user')->user();
		if($user) {

			$this->action_log->create([
				'user_id' => $user->id,
				'controller' => 'AuthController',
				'function' => 'Profile'
			]);

			return response()->json([
				'data' => $user,
				'status' => 1
			]);	
		}
		return response()->json([
			'status' => 0
		]);	
    }

	public function logout()
	{
		$this->action_log->create([
			'user_id' => auth('user')->user()->id,
			'controller' => 'AuthController',
			'function' => 'Logout'
		]);

		auth('user')->logout();
		return response()->json([
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
        if($x == 'register') {

        	$rules = [
	            'username' => 'required|unique:user',
	            'password' => 'required|between:5,255|confirmed',
	            'user_type_id' => 'required|integer',
	            'group_id' => 'required|integer',
	            'pin' => 'digits:6',
	        ];

        }

        $validate =  Validator::make($request->all(), $rules, $messages);

		return $validate;
    }
    
}
