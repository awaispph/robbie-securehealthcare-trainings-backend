<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'notes',
        'status',
    ];

    public function getFullNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_candidates')
            ->withPivot(['registered_at'])
            ->withTimestamps();
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function attendance()
    {
        return $this->hasMany(EventCandidateCourse::class);
    }
}
