<?php

namespace App\Models;

use App\Models\CourseAvailability;
use App\Models\CourseCategory;
use App\Models\RatingsToInstructor;
use App\Models\CourseLanguage;
use App\Models\CourseTag;
use App\Models\CourseLevel;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $hidden = ["updated_at"];

    /**
     * Get the course's name.
     *
     * @param  string  $value
     * @return string
     */
    public function getNameAttribute($value)
    {
        return ucfirst($value);
    }

    public function getCreatedAtAttribute($value)
    {
        return date("F Y", strtotime($value));
    }

    /**
     * Get the Image Link.
     *
     * @param  string  $value
     * @return string
     */
    public function getCoverAttribute($value)
    {
        $host = request()->getSchemeAndHttpHost();
        return $host . '/' . $value;
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    //course categories
    public function categories()
    {
        return $this->hasMany(CourseCategory::class);
    }

    //course languages
    public function languages()
    {
        return $this->hasMany(CourseLanguage::class);
    }

    //course availability
    public function avail()
    {
        return $this->hasMany(CourseAvailability::class)->whereNull('parent_id');
    }

    //course tags
    public function tags()
    {
        return $this->hasMany(CourseTag::class);
    }

    //course tags
    public function levels()
    {
        return $this->hasMany(CourseLevel::class);
    }

    //course tags
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    //course tags
    public function bookingsTime($type, $date)
    {
        if ($type == 'today') {
            return $this->hasMany(Booking::class)->whereDate('created_at', $date);
        } elseif ($type == 'week') {
            return $this->hasMany(Booking::class)->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
        } elseif ($type == 'month') {
            return $this->hasMany(Booking::class)->whereMonth('created_at', $date);
        } elseif ($type == 'year') {
            return $this->hasMany(Booking::class)->whereYear('created_at', $date);
        } elseif ($type == 'custom') {
            return $this->hasMany(Booking::class)->whereBetween('created_at', [$date[0], $date[1]]);
        }
    }

    //total function
    public function getTotalAmountAttribute($type, $date)
    {
        return $this->bookingsTime($type, $date)->sum('amount');
    }

    //course tags
    public function ratings()
    {
        return $this->hasMany(RatingsToInstructor::class);
    }

    //course tags
    public function getAverageRatingAttribute()
    {
        return $this->ratings->average('rating');
    }

    /**
     * Anonymous scope
     */
    public function scopeCurrent($query, $user_id = '')
    {
        $id = empty($user_id) ? Auth::user()->id : $user_id;
        return $query->where('user_id', $id);
    }

    //get private session
    public function scopeGetPublicSession($query)
    {
        return $query->where('private', 0)->where('deleted',0);
    }
    public function scopeGetFilterCourses($query,$keyword)
    {
        if($keyword != ""){
          return $this->with(['users'=> function ($query)use ($keyword) {
                        $query->where('first_name', 'LIKE', '%' . $keyword . '%')
                        ->orWhere('last_name', 'LIKE', '%' . $keyword . '%');
                    }]);
        }
        else{
            return $query->where('private', 0);
        }
    }
    public function scopeWithWhereHas($query, $relation, $constraint){
     return $query->whereHas($relation, $constraint)
     ->with([$relation => $constraint]);
    }
    public function scopeGetAllCourses($query,$data)
    {
        if($data['type'] == 3){}
        else{
            // $query = $this->with(['avail' => function ($query) use ($data) {
            //             $query->where(function ($queryWhere) use ($data){
            //                 $queryWhere->whereIn('repeats',['Daily','Weekly'])
            //                             ->whereBetween('minutes',[$data['from'],$data['to']]);
            //             });
            //             $query->orWhere(function ($queryWhereRepeat) use ($data){
            //                 $queryWhereRepeat->whereIn('repeats', ['No Repeat'])
            //                     ->whereDate('when','>',date('Y-m-d'))
            //                         ->whereBetween('minutes',[$data['from'],$data['to']]);
            //             });
            //         }])
            //         ->where('private',0)
            //         ->toSql();
            //         return $query;
          return $query->where('private', 0);            
        }
    }
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id'               => 'string',
        'user_id'          => 'string',
        'title'            => 'string',
        'image'            => 'string',
        'level'            => 'string',
        'cover'            => 'string',
        'price'            => 'string',
        'description'      => 'string',
        'price_to_you'     => 'string',
        'price_to_student' => 'string',
        'session_time'     => 'string',
        'private'          => 'string',
        'private_session'  => 'string',
        'featured'         => 'string',
        'created_at'       => 'string',
    ];
}
