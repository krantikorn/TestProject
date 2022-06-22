<?php

namespace App\Models;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $hidden  = ["created_at", "updated_at"];
    //protected $table   = 'course_categories';

    public function course()
    {
        return $this->belongsTo(Course::class);
    }
}
