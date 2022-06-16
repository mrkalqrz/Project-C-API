<?php

namespace App\Http\Controllers;

use App\Models\Action_log;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActionLogController extends Controller
{
    public function get(Request $request)
	{
		$action_log = Action_log::with(['user'])->find($request->id);

		$status = 0;

		if($action_log) {
			$status = 1;
		}

        return response()->json([
			'data' => $action_log,
			'status' => $status
		]);
	}

	public function list(Request $request)
	{
		$orWhere_columns = [
			'action_log.controller',
			'action_log.function',
			'action_log.note'
        ];

        $key = ($request->search_key) ? $request->search_key : '';

        if($request->search_key){
            $key = $request->search_key;
        }

        $limit = ($request->limit) ? $request->limit : 50;
        $sort_column = ($request->sort_column) ? $request->sort_column : 'created_at';
        $sort_order = ($request->sort_order) ? $request->sort_order : 'desc';

		$action_log = Action_log::with(['user'])->where(function ($q) use ($orWhere_columns, $key) {
                            foreach ($orWhere_columns as $column) {
                                $q->orWhere($column, 'LIKE', "%{$key}%");
                            }
                        });

		if($request->user_id){	
			$action_log = $action_log->where('user_id', $request->user_id);	
		}	
        if($request->from && $request->to){
            $action_log = $action_log->whereBetween('created_at', [Carbon::parse($request->from)->format('Y-m-d H:i:s'), Carbon::parse($request->to)->format('Y-m-d H:i:s')]);
        }

        $action_log = $action_log->orderBy($sort_column, $sort_order)->paginate($limit);

        return response()->json([
			'data' => $action_log,
			'status' => 1
		]);
	}
}
