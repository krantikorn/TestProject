<?php

namespace App\Models;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavCourse extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $hidden  = ["id", "created_at", "updated_at"];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id'        => 'string',
        'course_id' => 'string',
        'user_id'   => 'string',
    ];
}
