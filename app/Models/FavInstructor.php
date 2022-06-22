<?php

namespace App\Models;

use App\Models\Course;
use Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FavInstructor extends Model
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
        'id'            => 'string',
        'instructor_id' => 'string',
        'user_id'       => 'string',
    ];

    /**
     * Anonymous scope
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('instructors', function (Builder $builder) {
            $builder->where('user_id', Auth::user()->id);
        });
    }
}
