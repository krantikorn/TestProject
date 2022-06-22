<?php

namespace App\Models;

use App\Models\UserCategory;
use App\Models\UserDetails;
use App\Models\Booking;
use App\Models\Education;
use App\Models\FavCourse;
use App\Models\FavInstructor;
use Auth;
use App\Models\RatingsToInstructor;
use App\Models\Certificate;
use App\Models\Experience;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'account_type',
        'login_type',
        'social_id'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'id'                => 'string',
        'first_name'        => 'string',
        'last_name'         => 'string',
        'account_type'      => 'string',
        'email'             => 'string',
        'phone'             => 'string',

    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

    public function UserDetails()
    {
        return $this->hasMany(UserDetails::class);
    }
    //booking for student
    public function booking()
    {
        return $this->hasMany(Booking::class);
    }

    public function categories()
    {
        return $this->hasMany(UserCategory::class, 'user_id', 'id');
    }

    public function education()
    {
        return $this->hasMany(Education::class);
    }

    public function experience()
    {
        return $this->hasMany(Experience::class);
    }

    public function certificate()
    {
        return $this->hasMany(Certificate::class);
    }

    public function course()
    {
        return $this->hasMany(Course::class);
    }

    /******************ratings association******************/
    public function ratings()
    {
        return $this->hasMany(RatingsToInstructor::class);
    }

    /******************Instructor ratings association******************/
    public function getInstructorRatings()
    {
        return $this->hasMany(RatingsToInstructor::class, 'instructor_id', 'id');
    }

    //course tags
    public function getAverageRatingAttribute()
    {
        return $this->getInstructorRatings->average('rating');
    }


    /**
     * Anonymous scope
     */
    public function getCountRatings($users_id = '') :string
    {
        $rating = $this->getInstructorRatings->count();
        if ($rating > 0) {
            return $rating;
        }
        return 0;
    }

    /**
     * Student scope
     */
    public function scopeStudents($query)
    {
        return $query->where([['account_type', '=', 0], ['is_admin', '!=', 1]]);
    }

    /**
     * Teacher scope
     */
    public function scopeTeachers($query)
    {
        return $query->where([['account_type', '=', 1], ['is_admin', '!=', 1]]);
    }



    /**
     * Anonymous scope
     */
    public function getRatingCurrent($course_id = '') :string
    {
        $rating = $this->ratings->where("course_id", $course_id)->first();
        if ($rating) {
            return $rating->rating;
        }
        return 0;
    }

    /**
     * Anonymous scope
     */
    public function getCommentCurrent($course_id = '') :string
    {
        $rating = $this->ratings->where("course_id", $course_id)->first();
        if ($rating) {
            return $rating->comments ?? "";
        }
        return "";
    }

    /**
     * Anonymous scope
     */
    public function getRatings($course_id = '') :string
    {
        $rating = $this->ratings->where("course_id", $course_id)->count();
        if ($rating > 0) {
            return 1;
        }
        return 0;
    }

    /******************fav course association******************/
    public function favoriteCourse()
    {
        return $this->hasMany(FavCourse::class);
    }

    /**
     * Anonymous scope
     */
    public function getFavorite($course_id = '') :string
    {
        $rating = $this->favoriteCourse->where("course_id", $course_id)->count();
        if ($rating > 0) {
            return 1;
        }
        return 0;
    }

    /******************fav instructor association******************/
    public function favoriteInstructor()
    {
        return $this->hasMany(FavInstructor::class);
    }

    /**
     * Anonymous scope
     */
    public function getInstructor($instructor_id = '') :string
    {
        $rating = $this->favoriteInstructor->where("instructor_id", $instructor_id)->count();
        if ($rating > 0) {
            return 1;
        }
        return 0;
    }

    /**
     * Anonymous scope
     */
    public function scopeInstructor($query)
    {
        return $query->where('account_type', 1);
    }

    public function updateMeta($name, $value)
    {
        try {            
            if( $name == 'fcm_token' ){
                $user_details = UserDetails::where('meta_value',$value)->where('meta_key',$name)->first();
                if(empty($user_details)){
                    $meta               = new UserDetails;
                    $meta->meta_key     = $name;
                    $meta->meta_value   = $value;
                    $meta->user_id      = Auth::user()->id;
                    $meta->save();
                }else{
                    $user_details->user_id = Auth::user()->id;
                    
                    $user_details->save();
                }
            }else{
                $meta = $this->UserDetails->where("meta_key", $name)->first();
                if (!$meta) {
                    $meta           = new UserDetails;
                    $meta->meta_key = $name;
                }
                $meta->meta_value = $value;
                $result           = $this->UserDetails()->save($meta);
            }
        } catch (\Exceptions $e) {
            return $this->responseWithError($e->getMessage(), 409);
        }

        return true;
    }

    /**
     * Anonymous scope
     */
    /*protected static function boot()
    {
        parent::boot();
        static::addGlobalScope('authenticated', function (Builder $builder) {
            $builder->where('is_admin', '!=', 1);
        });
    }*/
}
