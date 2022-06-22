<?php

namespace App\Models;

use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RatingsToInstructor extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $hidden = ["user_id", "created_at", "updated_at"];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id'             => 'string',
        'user_id'        => 'string',
        'instructor_id'  => 'string',
        'course_id'      => 'string',
        'rating'         => 'string',
        'comments'       => 'string',
    ];

    //association
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
