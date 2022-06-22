<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SessionExclusion extends Model
{
    use HasFactory;


    public function courseAvailability()
    {
        return $this->belongsTo(CourseAvailability::class, 'availability_id', 'id');
    }
}
