<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CandidateCourseDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'candidate_id',
        'course_id',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
        'file_size' => 'integer',
    ];

    protected $appends = ['url'];

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

    public function getUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        $disk = config('filesystems.default') === 's3' ? 's3' : 'public';
        
        if ($disk === 's3') {
            return Storage::disk('s3')->temporaryUrl($this->file_path, now()->addMinutes(60));
        }

        return Storage::disk('public')->url($this->file_path);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' bytes';
    }
}
