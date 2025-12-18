<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trainer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'status',
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function hasEvents(): bool
    {
        return $this->events()->exists();
    }
}
