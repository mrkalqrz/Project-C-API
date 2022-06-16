<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Action_log;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Events\TransactionStatus;

class TransactionController extends Controller
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

		$transaction = Transaction::with(['user'])->find($request->id);

		$status = 0;

		if($transaction) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $transaction->id,
				'controller' => 'TransactionController',
				'function' => 'Get'
			]);

			$status = 1;
		}

        return response()->json([
			'data' => $transaction,
			'status' => $status
		]);
	}

	public function list(Request $request)
	{
		$orWhere_columns = [
			'transaction.amount'
        ];

        $key = ($request->search_key) ? $request->search_key : '';

        if($request->search_key){
            $key = $request->search_key;
        }

        $limit = ($request->limit) ? $request->limit : 50;
        $sort_column = ($request->sort_column) ? $request->sort_column : 'created_at';
        $sort_order = ($request->sort_order) ? $request->sort_order : 'desc';

		$transaction = Transaction::with(['user'])->where(function ($q) use ($orWhere_columns, $key) {
                            foreach ($orWhere_columns as $column) {
                                $q->orWhere($column, 'LIKE', "%{$key}%");
                            }
                        });

		if($request->user_id){	
			$transaction = $transaction->where('user_id', $request->user_id);	
		}
		if($request->type){	
			$transaction = $transaction->where('type', $request->type);	
		}
		if($request->user_type_id){	
			$transaction = $transaction->where('user_type_id', $request->user_type_id);	
		}
		if($request->update_by_id){	
			$transaction = $transaction->where('update_by_id', $request->update_by_id);	
		}		
        if($request->from && $request->to){
            $transaction = $transaction->whereBetween('created_at', [Carbon::parse($request->from)->format('Y-m-d H:i:s'), Carbon::parse($request->to)->format('Y-m-d H:i:s')]);
        }

        $transaction = $transaction->orderBy($sort_column, $sort_order)->paginate($limit);
		
		$this->action_log->create([
			'user_id' => auth('user')->user()->id,
			'controller' => 'TransactionController',
			'function' => 'List'
		]);

        return response()->json([
			'data' => $transaction,
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

		$user = User::find($request->user_id);
		$request['user_type_id'] = $user->user_type_id;

		if($request->file('image')) {
			$request->file('image')->move(public_path().'/images/', $img = 'ara_'.Str::random(15).'.jpg'); // image path sample http://api.sample.ara/images/
			$data = $request->except('image');
			$data['image'] = $img;
			$transaction = Transaction::create($data);
		} else {
			$transaction = Transaction::create($request->all());
		}
		
		$status = 0;

		if($transaction) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $transaction->id,
				'controller' => 'TransactionController',
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
        
        $transaction = Transaction::find($request->id);
        if($transaction->status > 0) {
            return response()->json([
                'message' => 'This transaction status is greater than 0',
                'status' => 0
            ]);
        }
        if($request->status == 1) {
            if(!$this->updateUserMoney($transaction)) {
				return response()->json([
					'status' => 0
				]);
			}
		
			broadcast( 
				new TransactionStatus(
					response()->json([
						'data' => $transaction,
					])
				)
			);
        }
        
		$request['update_by_id'] = auth('user')->user()->id;
        $transaction = Transaction::where('id', $request->id)->update($request->all());
		$status = 0;

		if($transaction > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'TransactionController',
				'function' => 'Update',
                'note' => json_encode($request->all())
			]);

			$status = 1;
		}

        return response()->json([
			'status' => $status
		]);
		
	}

    public function uploadImage(Request $request)
	{

		$validator = $this->validator($request, 'upload-image');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}
		
		$data = [];
		$request->file('image')->move(public_path().'/images/', $img = 'ara_'.Str::random(15).'.jpg'); // image path sample http://api.sample.ara/images/
		$data['image'] = $img;
        
        $transaction = Transaction::where('id', $request->id)->update($data);
		$status = 0;

		if($transaction > 0) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'TransactionController',
				'function' => 'UploadImage'
			]);

			$status = 1;
		}

        return response()->json([
			'status' => $status
		]);
		
	}

    public function deposit(Request $request) {
		if(auth('user')->user()->user_type_id != 4 && auth('user')->user()->user_type_id != 5) { // user type 5 = player
			$setting = Setting::find(1);
			$request['status'] = ($setting->status) ? 0 : 1;
		}
        return $this->depositWithdraw($request, 1); // 1=Deposit
    }
    
    public function withdraw(Request $request) {
		if(auth('user')->user()->user_type_id != 4 && auth('user')->user()->user_type_id != 5) { // user type 5 = player
			$setting = Setting::find(2);
			$request['status'] = ($setting->status) ? 0 : 1;
		}
        return $this->depositWithdraw($request, 2); // 2=Withdraw
    }

    private function depositWithdraw(Request $request, $type)
	{
		$validator = $this->validator($request, 'deposit-withdraw');

		if ($validator->fails()) {
			return response()->json([
				'messaage' => $validator->errors(),
				'status' => 0
			]);
		}
        $data = [
            'user_id' => auth('user')->user()->id,
			'user_type_id' => auth('user')->user()->user_type_id,
            'type' => $type,
            'amount' => $request->amount,
            'status' => ($request->status) ? $request->status : 0,
        ];
		
		if($data['status'] == 1) {
			$this->updateUserMoney($data);
		}
		
        $transaction = Transaction::create($data);

		$status = 0;

		if($transaction) {
			$this->action_log->create([
				'user_id' => auth('user')->user()->id,
				'_id' => $request->id,
				'controller' => 'TransactionController',
				'function' => 'depositWithdraw',
				'note' => 'amount: '.$request->amount,
			]);

			$status = 1;
		}

        return response()->json([
			'status' => $status
		]);
		
	}

	private function updateUserMoney($transaction) {
		try {
			if($transaction['type'] == 1) {
				if($transaction['user_type_id'] != 4 && $transaction['user_type_id'] != 5) {
					User::where('id', auth('user')->user()->id)->update(['money' => DB::raw('(money + '. $transaction['amount'] .')')]);
					return true;
				}
				else if($transaction['user_type_id'] == 5) { 
					User::where('id', auth('user')->user()->id)->update(['money' => DB::raw('(money + '. $transaction['amount'] .')')]);
				} else {
					if(auth('user')->user()->money < $transaction['amount']) return false;
					User::where('id', auth('user')->user()->id)->update(['money' => DB::raw('(money - '. $transaction['amount'] .')')]);
				}
				User::where('id', $transaction['user_id'])->update(['money' => DB::raw('(money + '. $transaction['amount'] .')')]);
			} else {
				if($transaction['user_type_id'] != 4 && $transaction['user_type_id'] != 5) {
					User::where('id', auth('user')->user()->id)->update(['money' => DB::raw('(money - '. $transaction['amount'] .')')]);
					return true;
				}
				else if($transaction['user_type_id'] == 5) { 
					if(auth('user')->user()->money < $transaction['amount']) return false;
					User::where('id', auth('user')->user()->id)->update(['money' => DB::raw('(money - '. $transaction['amount'] .')')]);
				} else {
					User::where('id', auth('user')->user()->id)->update(['money' => DB::raw('(money + '. $transaction['amount'] .')')]);
				}
				User::where('id', $transaction['user_id'])->update(['money' => DB::raw('(money - '. $transaction['amount'] .')')]);
			}
			return true;
		} catch(\Exception $e) {
			return false;
		}
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
        		'user_id' => 'required|integer',
        		'type' => 'required|integer',
        		'amount' => 'required',
				'image' => 'image|mimes:jpg,png,jpeg,gif,svg|max:2048'
	        ];
        }
        else if($x == 'update') {
        	$rules = [
        		'id' => 'required|integer',
	            'status' => 'integer',
	        ];
        }
        else if($x == 'deposit-withdraw') {
        	$rules = [
	            'amount' => 'required',
	        ];
        }
        else if($x == 'upload-image') {
        	$rules = [
	            'id' => 'required|integer',
	            'image' => 'image|mimes:jpg,png,jpeg,gif,svg|max:2048'
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

