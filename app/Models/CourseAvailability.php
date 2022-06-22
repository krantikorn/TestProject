<?php

namespace App\Models;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseAvailability extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $hidden  = ["created_at", "updated_at"];
    protected $table   = 'course_availability';

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function getCreatedAtAttribute($value)
    {
        return date("F Y", strtotime($value));
    }

    public function availabilityExclusion()
    {
        return $this->hasMany(SessionExclusion::class, 'availability_id');
    }

    /**
     * The attributes that should be cast.

     *
     * @var array
     */
    protected $casts = [
        'id'            => 'string',
        'course_id'     => 'string',
        'when'          => 'string',
        'start_date'    => 'string',
        'end_date'      => 'string',
        'repeats'       => 'string',
        'slots'         => 'string',
    ];
}
