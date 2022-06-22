<?php

namespace App\Models;

use App\Models\Course;
use App\Models\CourseAvailability;
use App\Models\PaymentMethod;
use App\Models\User;
use Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    protected $hidden = ["updated_at"];

    public function users()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public static function searchQuery($query)
    { 
        return empty($query) ? static::query()
                : static::where('bookings.email', 'like', '%'.$query.'%')
                    //->orWhere('users.first_name', 'like', '%'.$query.'%')
                    //->orWhere('users.last_name', 'like', '%'.$query.'%')
                    ->orWhere(\DB::raw("concat(users.first_name, ' ', users.last_name)"), 'like', '%'.$query.'%')
                    ->orWhere(\DB::raw("concat(instructor.first_name, ' ', instructor.last_name)"), 'like', '%'.$query.'%')
                    ->orWhere('instructor.email', 'like', '%'.$query.'%')
                    ->orWhere('courses.title', 'like', '%'.$query.'%');
           
    }

    public function course()
    {
        return $this->hasOne(Course::class, 'id', 'course_id');
    }
    /*//course PaymentMethod
    public function payments()
    {
        return $this->hasOne(PaymentMethod::class);
    }*/

    //course availability
    public function availability()
    {
        return $this->hasOne(CourseAvailability::class, 'id', 'availability_id');
    }

    /*//accessor Payment Method
    public function getPaymentMethodAttribute()
    {
        return $this->payment_method;
    }*/

    /**
     * Anonymous scope
     */
    public function scopeCurrent($query, $user_id = '')
    {
        $id = empty($user_id) ? Auth::user()->id : $user_id;
        return $query->where('user_id', $id);
    }

    public function getCreatedAtAttribute($value)
    {
        return date("F Y", strtotime($value));
    }
    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'id'              => 'string',
        'user_id'         => 'string',
        'course_id'       => 'string',
        'availability_id' => 'string',
        'when'            => 'string',
        'month'           => 'string',
        'year'            => 'string',
        'sessions'        => 'string',
        'email'           => 'string',
        'phone'           => 'string',
        'payment_method'  => 'string',
        'transaction_id'  => 'string',
        'amount'          => 'string',
    ];
}
