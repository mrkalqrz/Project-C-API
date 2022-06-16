<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use DateTimeInterface;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'schedule';

    protected $fillable = [
        'arena_id',
        'user_id',
        'event_name',
        'rake_percentage',
        'total_fights',
        'min_payout',
        'max_payout',
        'max_draw_bet',
        'enable_draw_bet',
        'draw_rake',
        'print_count',
        'enable_claiming',
        'datetime',
        'open_at',
        'close_at',
        'status'
    ];
    
    protected $hidden = [
    ];

    public function arena()
    {
        return $this->belongsTo(Arena::class);
    }

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