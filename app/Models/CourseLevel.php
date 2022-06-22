<?php

namespace App\Models;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseLevel extends Model
{
    use HasFactory;

    protected $table    = 'course_level';
    protected $guarded  = [];
    protected $hidden   = ["id", "course_id", "level_id", "created_at", "updated_at"];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id'        => 'string',
        'course_id' => 'string',
        'level_id'  => 'string',
    ];
}
