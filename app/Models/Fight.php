<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use DateTimeInterface;

class Fight extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'fight';

    protected $fillable = [
        'arena_id',
        'schedule_id',
        'admin_id',
        'fight_no',
        'rake_percentage',
        'result',
        'announcement',
        'confirmed',
        'meron_img',
        'wala_img',
        'meron_bet',
        'wala_bet',
        'draw_bet',
        'regrade_count',
        'claim_status',
        'status',
    ];
    
    protected $hidden = [
    ];

    public function arena()
    {
        return $this->belongsTo(Arena::class);
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
