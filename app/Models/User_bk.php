<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use DateTimeInterface;

class User_bk extends Authenticatable implements JWTSubject
{
    use Notifiable, HasFactory, SoftDeletes;

    protected $connection = "mysql1";
    protected $table = 'user';
    protected $guard = 'user';

	protected $fillable = [
		'user_type_id',
		'group_id',
		'username',
		'firstname',
		'lastname',
		'email',
		'phone',
		'password',
		'money',
		'max_bet',
		'max_draw_bet',
		'pin',
		'last_login',
		'status'
	];

	protected $hidden = [
		'password'
	];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function user_type()
    {
        return $this->belongsTo(User_type::class);
    }

	public function getJWTIdentifier()
	{
		return $this->getKey();
	}

	public function getJWTCustomClaims()
	{
		return ['guard' => 'user', 'user_type_id' => $this->user_type_id];
	}

    public function getCreatedAtAttribute($date)
    {
        return Carbon::parse($date)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($date)
    {
        return Carbon::parse($date)->format('Y-m-d H:i:s');
    }
}
