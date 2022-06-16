<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use DateTimeInterface;

class Action_log extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'action_log';

    protected $fillable = [
        'user_id',
        '_id',
        'controller',
        'function',
        'note'
    ];
    
    protected $hidden = [
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
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
