<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'candidate_id',
        'event_id',
        'course_id',
        'type',
        'certificate_number',
        'issued_date',
        'expiry_date',
        'published',
        'published_at',
        'passed_course_ids',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'published' => 'boolean',
        'published_at' => 'datetime',
        'passed_course_ids' => 'array',
    ];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public static function generateNumber()
    {
        // Get the last certificate with STS- format, starting from STS-1001
        $lastCert = self::where('certificate_number', 'LIKE', 'STS-%')
            ->orderBy('id', 'desc')
            ->first();
        
        if ($lastCert && $lastCert->certificate_number) {
            // Extract number from format STS-XXXX
            $lastNumber = (int) str_replace('STS-', '', $lastCert->certificate_number);
            $nextNumber = max($lastNumber + 1, 1001); // Ensure minimum 1001
        } else {
            $nextNumber = 1001;
        }
        
        return 'STS-' . $nextNumber;
    }
}
