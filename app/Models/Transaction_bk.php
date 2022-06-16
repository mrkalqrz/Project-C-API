<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use DateTimeInterface;

class Transaction_bk extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = "mysql1";
    protected $table = 'transaction';

	protected $fillable = [
		'user_id',
		'user_type_id',
		'type',
		'amount',
		'image',
		'note',
		'status',
	];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function user_type()
    {
        return $this->belongsTo(User_type::class);
    }
    
    public function getCreatedAtAttribute($date)
    {
        return Carbon::parse($date)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($date)
    {
        return Carbon::parse($date)->format('Y-m-d H:i:s');
    }

    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
