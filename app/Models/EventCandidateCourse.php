<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventCandidateCourse extends Model
{
    protected $table = 'event_candidate_courses';

    protected $fillable = [
        'event_id',
        'candidate_id',
        'course_id',
        'attended',
        'result',
        'notes',
        'marked_at',
    ];

    protected $casts = [
        'marked_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
