<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location_id',
        'trainer_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function trainer()
    {
        return $this->belongsTo(Trainer::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'event_courses')
            ->withPivot(['scheduled_date', 'scheduled_time', 'order'])
            ->withTimestamps()
            ->orderBy('event_courses.order');
    }

    public function candidates()
    {
        return $this->belongsToMany(Candidate::class, 'event_candidates')
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

    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now()->toDateString())
            ->where('status', 'published')
            ->orderBy('start_date');
    }
}
