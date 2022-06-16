<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use DateTimeInterface;

class Group extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'group';

    protected $fillable = [
        'arena_id',
        'name',
        'owner',
        'address',
        'description',
        'status'
    ];
    
    protected $hidden = [
    ];

    public function arena()
    {
        return $this->belongsTo(Arena::class);
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
