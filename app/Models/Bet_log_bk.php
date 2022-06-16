<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use DateTimeInterface;

class Bet_log_bk extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = "mysql1";
    protected $table = 'bet_log';

    protected $fillable = [
        'user_id',
        'schedule_id',
        'fight_id',
        'bet_select',
        'bet_amount',
        'status',
        'result',
        'result_amount',
        'barcode',
        'claimed',
        'reprint_count',
        'remark'
    ];
    
    protected $hidden = [
    ];

    public function fight()
    {
        return $this->belongsTo(Fight::class);
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
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
