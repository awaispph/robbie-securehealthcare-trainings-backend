<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'status',
        'expiry_duration',
        'expiry_unit',
    ];

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_courses')
            ->withPivot(['scheduled_date', 'scheduled_time', 'order'])
            ->withTimestamps();
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    /**
     * Calculate expiry date from a given start date based on course expiry settings
     */
    public function calculateExpiryDate(Carbon $startDate): ?Carbon
    {
        if (!$this->expiry_duration || !$this->expiry_unit) {
            return null;
        }

        return match ($this->expiry_unit) {
            'day' => $startDate->copy()->addDays($this->expiry_duration),
            'week' => $startDate->copy()->addWeeks($this->expiry_duration),
            'month' => $startDate->copy()->addMonths($this->expiry_duration),
            'year' => $startDate->copy()->addYears($this->expiry_duration),
            default => null,
        };
    }

    /**
     * Get formatted expiry duration string (e.g., "1 Year", "6 Months")
     */
    public function getExpiryLabelAttribute(): ?string
    {
        if (!$this->expiry_duration || !$this->expiry_unit) {
            return null;
        }

        $unit = $this->expiry_duration === 1 
            ? rtrim($this->expiry_unit, 's') 
            : $this->expiry_unit . 's';

        return $this->expiry_duration . ' ' . ucfirst($unit);
    }
}
