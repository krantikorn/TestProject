<?php

namespace App\Traits;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Following;
use App\Models\CourseAvailability;
use App\Models\SessionExclusion;
use App\Models\Setting;
use App\Models\Booking;
use Carbon\Carbon;
use App\Models\Notification;
use App\Models\User;
//use App\Models\UserCategories;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use DateTime;
use DatePeriod;
use DateInterval;
use When\When;

trait Meta
{
    //get user details
    public $isCourseApi = false , $isSearchCall = false;
    public function getMetaValue($users, $metaKey)
    {
        if (!empty($users->UserDetails)) {
            foreach ($users->UserDetails as $key => $details) {
                if (strcmp($details->meta_key, $metaKey) == 0 && $details->meta_value !='') {
                    return $details->meta_value;
                }
            }
        }
        return null;
    }

    //delete old files for users, if uploaded
    public function unlink_files($user)
    {
        $image = $this->getMetaValue($user, 'image');
        if (!empty($image)) {
            File::delete(base_path() . '/' . $image);
        }
    }

    public function evaluateRespondVerifiedData($users)
    {

        $name         = $users->name;
        $phone        = $users->phone;
        $country_code = $this->getMetaValue($users, 'country_code');
        $verified     = $this->getMetaValue($users, 'verified');
        $data         = array(
            'user_id'      => $users->id,
            'name'         => $name ?? null,
            'phone'        => $phone ?? null,
            'country_code' => $country_code ?? null,
            'verified'     => $verified ?? 0,
        );
        $data['email'] = $users->user_email;
        return $data;
    }

    //set instructor structure
    public function getInstructorData($user_id = '',$private = false)
    {
        $experiences = Experience::current($user_id)->get(); //get all experience
        $educations  = Education::current($user_id)->get(); //get all educations
        $certificate = Certificate::current($user_id)->get(); //get all certificate
        $course      = Course::query();
        if($private){
           $course->where('private',0);
        }
        $course = $course->current($user_id)->get(); //get all course

        $user  = Auth::user(); //current user
        $getCurrentUser  = Auth::user(); //current user
        if (!empty($user_id)) {
            $user = User::find($user_id);
        }
        
        $image = $this->getMetaValue($user, 'image'); //image
        $host  = request()->getSchemeAndHttpHost();

        $getExperience  = $this->getExperience($experiences); //getting experience structure
        $getEducation   = $this->getEducation($educations); //getting educations structure
        $getCertificate = $this->getCertificate($certificate); //getting certificate structure

        $getCourse = array();

        foreach ($course as $key => $value) {
            $courseFilter = $this->getCourseResponse($value);
            if(!empty($courseFilter['availability'])){
                $getCourse[] = $courseFilter; //getting Course structure
            }   
        }

        $data = array(
            'id'          => $user->id,
            'name'        => $user->first_name . ' ' . $user->last_name,
            'tag_line'    => $this->getMetaValue($user, 'tag_line') ?? "", //tag_line
            'ratings'     => "",
            'image'       => !empty($image) ? $host . '/' . $image : "",
            'about'       => $this->getMetaValue($user, 'about') ?? "", //tag_line
            'experience'  => $getExperience,
            'is_fav'      => $getCurrentUser->getInstructor($user->id),
            'education'   => $getEducation,
            'certificate' => $getCertificate,
            'course'      => $getCourse,
        );

        return $data;
    }

    //get Instructor Availability
    public function getInstructorAvailablity($availability = '', $course = '', $instructorDash = '', $month = '', $day = '', $year = '')
    {
        $count = 0;
        if (!empty($course->bookings)) {
            foreach ($course->bookings as $booked) {
                if ($booked->availability_id == $instructorDash) {
                    $count++;
                }
            }
        }
        $countBookings        = $course->bookings ?? 0;
        $pending_slots        = $availability->slots - $count;
        $data['id']           = $availability->id;
        $data['course']       = $this->getCourseStructure($course, $month, $day, $year);
        $data['booking_id']   = "";
        $data['when_utc']     = $availability->when ?? '';
        $data['is_private']   = $course->private ?? "";
        $data['slots']        = $availability->slots ?? '';
        $data['pending']      = (string) $count; //$pending_slots;
        $data['availability'] = (object) [];
        $data['instructor']   = $this->getInstructor($course->users->id);
        /*
        $data['slots']         = $availability->slots ?? '';
        $data['pending_slots'] = (string) $pending_slots;
        $data['availability']  = $this->getCalendarAvailability($course->bookings[0]->availability);
        $data['instructor']    = $this->getInstructor($course->users->id);*/

        return $data;
    }

    //get instructor group calendar
    public function getInstructorGroupCalendar($course = '')
    {
        $data         = array();
        $setTimeArray = array();
        if (!empty($course->avail)) {
            foreach ($course->avail as $key => $availability) {
                $data[$availability->created_at][] = $this->getInstructorAvailablity($availability, $course, $availability->id);
            }
        }

        foreach ($setTimeArray as $keyValue => $secondValue) {
            $data[] = array(
                'month_name' => $keyValue,
                'courses'    => $setTimeArray[$keyValue],
            );
        }

        return $data;
    }

    public function array_flatten($array)
    {
        if (!is_array($array)) {
            return false;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    //get instructor calendar
    public function getInstructorCalendar($course = '', $month = '', $day = '', $year = '')
    {
        $data = array();
        if (!empty($course->avail)) {
            foreach ($course->avail as $key => $availability) {
                $timestamp  = strtotime($availability->when);
                $repeats    = $availability->repeats;

                if (!empty($month) && !empty($day) && $repeats == 'No Repeat') {
                    if ($month == date("m", $timestamp) && $day == date("d", $timestamp) && $year == date("Y", $timestamp)) {
                        $data[] = $this->getInstructorAvailablity($availability, $course, $availability->id, $month, $day, $year);
                    }
                } elseif (!empty($month) && $repeats == 'No Repeat') {
                    if ($month == date("m", $timestamp)) {
                        $data[] = $this->getInstructorAvailablity($availability, $course, $availability->id, $month, $day, $year);
                    }
                } elseif (!empty($day) && $repeats == 'No Repeat') {
                    if ($day == date("d", $timestamp)) {
                        $data[] = $this->getInstructorAvailablity($availability, $course, $availability->id, $month, $day, $year);
                    }
                } elseif (!empty($year) && $repeats == 'No Repeat') {
                    if ($year == date("Y", $timestamp)) {
                        $data[] = $this->getInstructorAvailablity($availability, $course, $availability->id, $month, $day, $year);
                    }
                } else {
                    $data[] = $this->getInstructorAvailablity($availability, $course, $availability->id, $month, $day, $year);
                }
                break;
            }
        }

        return $data;
    }

    public function getCalendar($booking = '',  $month = '', $day = '', $year = '')
    {
        $data                  = array();
        $pending_slots         = $booking->availability->slots - count($booking->course->bookings);
        $data['course']        = $this->getCourseStructure($booking->course, '', $month, $day, $year);
       // return $data;
        $data['booking_id']    = $booking->id;
        $data['when_utc']      = $booking->when;
        $data['booking_status']= $booking->status;
        $data['is_private']    = $booking->course->private ?? "";
        $data['slots']         = $booking->course->avail[0]->slots;
        $data['pending_slots'] = (string) count($booking->course->bookings); //$pending_slots;
        // $data['availability']  = $this->getCalendarAvailability($booking->availability);
        $data['instructor']    = $this->getInstructor($booking->course->users->id);

        return $data;
    }

    public function getEarningsInstructor($course = '')
    {
        $data      = array();
        $flagAvail = 1;
        foreach ($course as $key => $value) {
            $courseAvail = $value->avail;

            foreach ($courseAvail as $k => $avail) {
                //if ($flagAvail <= 3) {
                    $data[] = $this->getCalendarAvailability($avail, $avail->id);
                //}
                //$flagAvail++;
            }
        }

        return $data;
    }

    public function getDashboardInstructor($course = '')
    {
        $data      = array();
        $flagAvail = 0;
        foreach ($course as $key => $value) {
            $courseAvail = $value->avail;

            foreach ($courseAvail as $k => $avail) {
                if ($flagAvail <= 3) {
                    $data[] = $this->getCalendarAvailability($avail, $avail->id);
                }
                $flagAvail++;
            }
        }

        /*foreach ($setTimeArray as $keyValue => $secondValue) {
        $data[] = array(
        'month_name' => $keyValue,
        'schedules'  => $setTimeArray[$keyValue],
        );
        }*/

        return $data;
    }

    //get experience structure
    public function getExperience($experiences = '')
    {
        $data = array();
        foreach ($experiences as $key => $value) {
            $data[] = array(
                'id'      => $value->id,
                'company' => $value->company,
                'title'   => $value->title,
                'summary' => $value->summary,
                'location'=> (string) $value->location ?? "",
                'from'    => array(
                    'month' => $value->from_month,
                    'year'  => $value->from_year,
                ),
                'to'      => array(
                    'month' => $value->to_month,
                    'year'  => $value->to_year,
                ),
            );
        }

        return $data;
    }

    //get education structure
    public function getEducation($educations = '')
    {
        $data = array();
        foreach ($educations as $key => $value) {
            $data[] = array(
                'id'      => $value->id,
                'company' => $value->institute,
                'title'   => $value->title,
                'location'=> (string) $value->location ?? "",
                'from'    => array(
                    'month' => $value->from_month,
                    'year'  => $value->from_year,
                ),
                'to'      => array(
                    'month' => $value->to_month,
                    'year'  => $value->to_year,
                ),
            );
        }

        return $data;
    }

    //get certificate structure
    public function getCertificate($certificate = '')
    {
        $data = array();
        foreach ($certificate as $key => $value) {
            $data[] = array(
                'id'      => $value->id,
                'company' => $value->institute,
                'title'   => $value->title,
                'location'=> (string) $value->location ?? "",
                'month'   => $value->month,
                'year'    => $value->year,
            );
        }

        return $data;
    }

    //get current user followings
    public function getFollowings($user_id = '')
    {
        $id             = $user_id ?? Auth::user()->id;
        $user           = User::find($id);
        $getFollowingId = array();
        if (!empty($user->UserFollowing)) {
            foreach ($user->UserFollowing as $key => $value) {
                $getFollowingId[] = $value->following_id;
            }
        }

        return $getFollowingId;
    }

    //get current user followers
    public function getFollowers($user_id = '')
    {
        $id             = $user_id ?? Auth::user()->id;
        $user           = Following::where('following_id', $id)->get();
        $getFollowersId = array();
        foreach ($user as $key => $value) {
            $getFollowersId[] = $value->user_id;
        }

        return $getFollowersId;
    }

    //get search result api
    public function searchResponse($searchResponse, $user_id, $checkFollowers)
    {
        $data         = array();
        $host         = request()->getSchemeAndHttpHost();
        $followingIDs = $this->getFollowings($user_id); //get current user getFollowings
        $followerIDs  = $this->getFollowers($user_id); //get current user followers

        //check if searchResponse is not empty
        if (!empty($searchResponse)) {
            foreach ($searchResponse as $key => $user) {
                //get following id
                $following = 0;
                if (!empty($followingIDs)) {
                    if (in_array($user->id, $followingIDs)) {
                        $following = 1;
                    }
                }

                //get followers id
                $followers = 0;
                if (!empty($followerIDs)) {
                    if (in_array($user->id, $followerIDs)) {
                        $followers = 1;
                    }
                }
                //check if for current user followers
                if ($checkFollowers == '0') {
                    if (in_array($user->id, $followingIDs)) {
                        $image  = $this->getMetaValue($user, 'image');
                        $data[] = array(
                            'id'        => (string) $user->id,
                            'name'      => $user->first_name . ' ' . $user->last_name ?? 'null',
                            'email'     => $user->email ?? 'null',
                            'image'     => !empty($image) ? $host . '/' . $image : 'null',
                            'following' => (string) $following,
                            'followers' => (string) $followers,
                        );
                    }
                } elseif ($checkFollowers == '1') {
                    if (in_array($user->id, $followerIDs)) {
                        $image  = $this->getMetaValue($user, 'image');
                        $data[] = array(
                            'id'        => (string) $user->id,
                            'name'      => $user->first_name . ' ' . $user->last_name ?? 'null',
                            'email'     => $user->email ?? 'null',
                            'image'     => !empty($image) ? $host . '/' . $image : 'null',
                            'following' => (string) $following,
                            'followers' => (string) $followers,
                        );
                    }
                } else {
                    $image  = $this->getMetaValue($user, 'image');
                    $data[] = array(
                        'id'        => (string) $user->id,
                        'name'      => $user->first_name . ' ' . $user->last_name ?? 'null',
                        'email'     => $user->email ?? 'null',
                        'image'     => !empty($image) ? $host . '/' . $image : 'null',
                        'following' => (string) $following,
                        'followers' => (string) $followers,
                    );
                }
            }
        }

        return $data;
    }

    //get all interest categories
    public function getAllInterests()
    {
        //#2
        $users           = User::find(Auth::user()->id);
        $categories      = $users->categories;
        $groupCategories = [];
        foreach ($categories as $key => $value) {
            $groupCategories[] = $value->category->id;
        }
        return $groupCategories;
    }

    // function for response without token
    public function evaluateRespondDataWithOutToken($users = '')
    {
        $groupCategories = [];
        if (!empty($users->categories)) {
            $categories      = $users->categories;
            foreach ($categories as $key => $value) {
                $groupCategories[] = $this->getSavedCategories($value);
            }
        }

        $settings           = [];
        $getSetting         = Setting::get();
        foreach ($getSetting as $keySetting => $valueSetting) {
            $settings[] = array(
                                'name' => $valueSetting->name,
                                'value' => $valueSetting->value
                        );
        }

        $first_name       = $users->first_name;
        $last_name        = $users->last_name;
        $phone            = $users->phone;
        $email            = $users->email;
        $educations       = $users->education;
        $experiences      = $users->experience;
        $certificate      = $users->certificate;

        $getExperience  = $this->getExperience($experiences); //getting experience structure
        $getEducation   = $this->getEducation($educations); //getting educations structure
        $getCertificate = $this->getCertificate($certificate); //getting certificate structure

        $course           = $users->course;
        $getCourse = array();
        foreach ($course as $key => $value) {
            $getCourse[] = $this->getCourseResponse($value); //getting Course structure
        }

        $account_type     = (string) $users->account_type;
        $fcm_token        = $this->getMetaValue($users, 'fcm_token'); //fcm_token
        $image            = $this->getMetaValue($users, 'image'); //fcm_token
        $about            = $this->getMetaValue($users, 'about'); //getting about from user details table
        $tag_line         = $this->getMetaValue($users, 'tag_line'); //getting tag_line from user details table
        $stripe_connect_id            = $this->getMetaValue($users, 'stripe_connect_id'); //getting stripe_connect_id from user details table
        $stripe_connect_email            = $this->getMetaValue($users, 'stripe_connect_email'); //getting stripe_connect_email from user details table
        $pushNotification = $users->pushNotifications->status ?? 0; //pushnotif

        /*$email_verified = $this->getMetaValue($users, 'email_verified');
        $phone_verified = $this->getMetaValue($users, 'phone_verified');*/
        $host = request()->getSchemeAndHttpHost();
        $data = array(
            'id'                   => (string) $users->id,
            'first_name'           => $first_name ?? "",
            'last_name'            => $last_name ?? "",
            'email'                => $email ?? "",
            'phone'                => $phone ?? "",
            'account_type'         => $account_type ?? "",
            'push_notification'    => (string) $pushNotification ?? "0",
            'fcm_token'            => $fcm_token ?? '',
            'interests'            => $groupCategories ?? "",
            'education'            => $getEducation,
            'experience'           => $getExperience,
            'courses'              => $getCourse,
            'certificate'          => $getCertificate,
            'about'                => $about ?? "",
            'settings'             => $settings,
            'tag_line'             => $tag_line ?? "",
            'stripe_connect_id'    => $stripe_connect_id ?? "",
            'stripe_connect_email' => $stripe_connect_email ?? "",
            //'verified'             => $verified,
            'image'                => !empty($image) ? $host . '/' . $image : "",
            'ratings'              => "",
        );

        return $data;
    }

    public function getAllBookingResponse($booked = '', $pass = 1)
    {
        $data = array(); 
        $pass = 1;
        foreach ($booked as $key => $booking) {
            $data[] = $this->getBookingResponse($booking, $pass);
        }

        return $data;
    }

    //get booking response
    public function getBookingResponse($booking = '', $pass = '')
    {
        $course                = $booking->course;
        $pending_slots         = $course->avail[0]->slots - count($course->bookings);
        $data['id']            = $booking->id;
        $data['course_id']     = $course->id;
        $data['booking_status']= $booking->status;
        $data['title']         = $course->title;
        $data['image']         = $course->image;
        $data['cover']         = $course->cover;
        $data['price']         = $course->price;
        $data['session_time']  = $course->session_time;
        $data['description']   = $course->description;
        $data['status']        = $course->private;
        $data['featured']      = $course->featured;
        $data['when_time']     = $booking->when;
        if ($pass == 1) {
            $user = Auth::user();
            $data['ratings']   = (string) round($user->getRatingCurrent($course->id), 2); //$course->getAverageRatingAttribute();
            $data['comment']   = (string) $user->getCommentCurrent($course->id); //$course->getAverageRatingAttribute();
            $data['is_rated']  = $user->getRatings($course->id);
        }
        $data['level']         = $course->level;
        $data['instructor']    = $this->getInstructor($course->user_id);
        $data['availability']  = $this->getBookingAvailability($booking->availability);
        $data['slots']         = $booking->availability->slots ?? 'null';
        $data['pending_slots'] = (string) count($course->bookings); //$pending_slots;
        $data['tags']          = $this->getTags($course->tags);
        $data['languages']     = $this->getLanguages($course->languages);
        $data['created_at']    = $course->created_at;
        //dd($data['tags']);
        return $data;
    }

    public function getCarbon($type = '', $start = '', $end = '')
    {
        switch ($type) {
            case 'today':
                return Carbon::today();
                break;
            case 'week':
                return now()->week;
                break;
            case 'month':
                return now()->month;
                break;
            case 'year':
                return now()->year;
                break;
            case 'custom':
                return array(Carbon::createFromFormat('d/m/Y', $start)->format('Y-m-d h:i:s'), Carbon::createFromFormat('d/m/Y', $end)->format('Y-m-d h:i:s'));
                break;
            default:
                return Carbon::today();
                break;
        }
    }

    //get Course Structure
    public function getCourseStructure($course, $instructorDash = '', $month = '', $day = '', $year = '')
    {
        $user = Auth::user();
        $status = '0';
        if(!empty($course->bookings)) {
            foreach ($course->bookings as $key => $value) {
                if ($value->user_id == Auth::user()->id) {
                    $status = '1';
                }
            }
        }
        $category_id = array();
        if (isset($course->categories[0])) {
            foreach ($course->categories as $k => $categoryValue) {
                $category_id[] = (string) $categoryValue->categories_id;
            }
            //$category_id = $course->categories[0]->categories_id;
        }

        $levels = array();
        if (isset($course->levels[0])) {
            foreach ($course->levels as $k => $courseLevel) {
                $levels[] = (string) $courseLevel->level->name;
            }
            //$category_id = $course->categories[0]->categories_id;
        }
/*
        $pending_slots = "";
        $count_slots = "";
        if (isset($course->avail[0])) {
            $pending_slots              = $course->avail[0]->slots - count($course->bookings);
            $count_slots                = count($course->bookings);
        }   */     
        //getting availability time
        //$getAvailability = array_values(array_filter($this->getAvailability($course->avail, $month, $day, $year, $course)));
        $getAvailability = $this->getAvailabilityNew($course->avail, $month, $day, $year);
        $temp  = new  \stdClass();
        foreach ($getAvailability as &$avail){
            foreach ($getAvailability as &$avail2){
                if($avail->when_utc <  $avail2->when_utc){
                    $temp = $avail;
                    $avail = $avail2;
                    $avail2 =$temp;
                }
            }
        }
       // echo'<pre>'; print_r(  usort($getAvailability, function($a, $b) {return $a->when_utc>$b->when_utc;}));
      //  if(empty($getAvailability)){
      //      return;
      //  }
        $data['id']               = $course->id;
        $data['title']            = $course->title;
        $data['cover']            = $course->cover;
        $data['price_to_you']     = $course->price_to_you ?? "";
        $data['price_to_student'] = $course->price_to_student ?? "";
        $data['description']      = $course->description;
        $data['private_session']  = $course->private_session;
        $data['is_private']       = $course->private ?? "";
        $data['categories_id']    = $category_id ?? "";
        $data['featured']         = $course->featured;
        $data['course_avail']     = $course->avail()->select('repeats','when as when_utc','slots')->first();
        $data['level']            = $levels;
        $data['rating']           = $this->getCourseRating($course);
        $data['session_time']     = $course->session_time ?? "";
        $data['is_fav']           = $user->getFavorite($course->id);
        $data['instructor']       = $this->getInstructor($course->user_id);
        $data['availability']     = $getAvailability;
        //$data['slots']            = isset($course->avail[0]) ? $course->avail[0]->slots : "";
        //$data['pending_slots']    = (string) $count_slots; //$pending_slots ?? "";
        $data['tags']             = $this->getTags($course->tags);
        $data['languages']        = $this->getLanguages($course->languages);
        $data['status']           = $status;
        $data['students']         = $this->getStudents($course, $instructorDash);
        $data['is_deleted']       = $course->deleted;
        $data['created_at']       = $course->created_at;

        return $data;
    }

   // get course all rating Avrg.
   public function getCourseRating($course){
       $totalRating = 0;
        foreach($course->ratings as $rating){
            $totalRating = $totalRating + $rating->rating;
        }
        if($course->ratings->count() >0)
        return (string)round($totalRating / $course->ratings->count() , 2);
        return '';
   } 
  //get Availability New
    public function getAvailabilityNew($availability, $month, $day, $year){
        $avalibilty=[];
        // In case of Create Course month and year is empty so we take current month and year to display records
        if($month == '' && $year == ''){
               $month = date('m', strtotime(Carbon::now()));
               $year  = date('Y', strtotime(Carbon::now()));
        }
        if(strlen($month)==1){
            $month = '0'.$month;
        }
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        //create date as yyyymmddThhiiss formeat for When package
        $endDate   = $year .$month.$daysInMonth. 'T235900Z';
        foreach($availability as $courseAvail){            
            // Get all booking of current availability
            $bookingCountArray  =     Booking::select('when',\DB::raw('count(*) as used_slots'))
                                ->where('availability_id',$courseAvail->id)
                                ->where('status',1)
                                ->groupBy('when')->get()->keyBy('when');
            $today =  '01';                   
            if($this->isSearchCall==true || $this->isCourseApi == true){ // check for search start from current date.. other from first day
                $today  =  date('d', strtotime(Carbon::now()));
            }                    
            $startDate = $year .$month .$today
                        .Carbon::create($courseAvail->when)->format('H').
                        Carbon::create($courseAvail->when)->format('i').'00';
            //if no repeats then No occurrences  
            // this check If  occurrences is terminate by user
            if(!empty($courseAvail->end_date)){
                if(date('Y-m-d', strtotime($courseAvail->end_date)) < date('Y-m-d', strtotime($endDate))){
                        $endDate  = $courseAvail->end_date;
                }
            }
            // Create all exclusion Strings 
           /* $exclusion ='';
            if(!empty($courseAvail->availabilityExclusion)){
                $exclusion = implode(',',$courseAvail->availabilityExclusion->pluck('exclusions')->toArray());
            }*/
            $exclusion=[];
            if(!empty($courseAvail->availabilityExclusion)){
                $exclusion = $courseAvail->availabilityExclusion->pluck('exclusions')->toArray();
            }          
            if($courseAvail->rule == 'FREQ=NO REPEAT'){
                if(date('Y-m-d', strtotime($courseAvail->when)) >= date('Y-m-d', strtotime($startDate))  && date('Y-m-d', strtotime($courseAvail->when)) <= date('Y-m-d', strtotime($endDate)) ){
                    $avalibiltyObj =  new  \stdClass();
                    $when_utc                   =   $courseAvail->when;
                    $avalibiltyObj->id          =   $courseAvail->id;
                    $avalibiltyObj->when_utc    =   $when_utc;
                    $avalibiltyObj->repeats     =   $courseAvail->repeats;
                    $avalibiltyObj->slots       =   $courseAvail->slots;
                    $avalibiltyObj->status      =   in_array($when_utc,$exclusion)?4:1;
                    $avalibiltyObj->pending     =   !empty($bookingCountArray[$when_utc])?$bookingCountArray[$when_utc]->used_slots:'0';
                    if(!($this->isCourseApi == true && $avalibiltyObj->status ==4)){
                        $avalibilty[]               =   $avalibiltyObj;
                    }
                    
                }
            }else{

                //check if getting same month availability when it creats                     
                if(  date('Y-m-d', strtotime($courseAvail->when)) >= date('Y-m-d', strtotime($startDate)) ){
                    $startDate = $courseAvail->when; 
                }
                if($courseAvail->rule == 'FREQ=WEEKLY'){    
                    // get day in String 'SU,MO....'        
                    $day  = Carbon::create($courseAvail->when)->format('l');
                    $day  = strtoupper(substr($day,0,2));
                    $rule = $courseAvail->rule.';BYDAY='.$day.';UNTIL='.$endDate;
                }else{
                    $rule  =  $courseAvail->rule.';UNTIL='.$endDate;
                }
                $r = new When();
                $r->RFC5545_COMPLIANT = When::IGNORE;
                $r->startDate(new DateTime($startDate))
                ->rrule($rule)
                //->exclusions($exclusion)
                  ->generateOccurrences();
                foreach($r->occurrences as $occurrences){
                    $avalibiltyObj =  new  \stdClass();
                    $when_utc                   =   $occurrences->format('Y-m-d H:i:s');
                    $avalibiltyObj->id          =   $courseAvail->id;
                    $avalibiltyObj->when_utc    =   $when_utc;
                    $avalibiltyObj->repeats     =   $courseAvail->repeats;
                    $avalibiltyObj->slots       =   $courseAvail->slots;
                    $avalibiltyObj->status      =   in_array($when_utc,$exclusion)?4:1;
                    $avalibiltyObj->pending     =   !empty($bookingCountArray[$when_utc])?$bookingCountArray[$when_utc]->used_slots:'0';

                                                    //$this->getSlots($when_utc,$courseAvail->id,
                                                    //$courseAvail->slots);//$pending_slots;
                    if(!($this->isCourseApi == true && $avalibiltyObj->status ==4)){
                        $avalibilty[]               =   $avalibiltyObj;
                    }
                }
            }    
        }
        return $avalibilty;       
    }
   /* public function getSlots($when,$availability_id,$slots){
       $bookingCount  =     Booking::where('when',$when)
                            ->where('availability_id',$availability_id)->count();
        return $slots - $bookingCount;
    } */
    //get course response
    public function getCourseResponse($course = '', $instructorDash = '',$isCourseApi = false, $isSearchCall = false)
    {   
        $this->isCourseApi = $isCourseApi;
        $this->isSearchCall = $isSearchCall;
        $data = $this->getCourseStructure($course, $instructorDash);       
        return $data;
    }

    public function getTags($tags = '')
    {
        $data = array();
        foreach ($tags as $key => $value) {
            $data[] = $value->tag;
        }

        return $data;
    }

    public function getLanguages($languages = '')
    {
        $data = array();
        foreach ($languages as $key => $value) {
            $data[] = $value->language_id;
        }

        return $data;
    }

    public function getStudents($course = '', $instructorDash = '')
    {
        $data = array();
        foreach ($course->bookings as $key => $value) {
            $rating = $course->ratings()->where('user_id',$value->users->id)->first();
            if (!empty($instructorDash)) {
                if ($value->availability_id == $instructorDash) {
                    $data[] = array(
                        'id'      => $value->users->id ?? "",
                        'name'    => $value->users->first_name . ' ' . $value->users->last_name ?? "",
                        'image'   => $this->getMetaValue($value->users, 'image') ?? "",
                        'ratings' => $rating->rating ?? '',
                        'booking_status' => $value->status,
                        'booking_when' => $value->when
                    );
                }
            } else {
                $data[] = array(
                    'id'                => $value->users->id ?? "",
                    'name'              => $value->users->first_name . ' ' . $value->users->last_name ?? "",
                    'image'             => $this->getMetaValue($value->users, 'image') ?? "",
                    'ratings'           => $rating->rating ?? '',
                    'booking_status'    => $value->status,
                    'booking_when'      => $value->when
                );
            }
        }
        return $data;
    }

    public function getCalendarAvailability($availability = '', $instructorDash = '')
    {
        $count = 0;
        if (!empty($availability->course->bookings)) {
            foreach ($availability->course->bookings as $booked) {
                if ($booked->availability_id == $instructorDash) {
                    $count++;
                }
            }
        }

        $pending_slots  =   $availability->slots - $count;
        $status         =   SessionExclusion::where([
                                'availability_id'   =>  $availability->id ?? "",
                                'exclusions'        =>  $availability->when ?? ""
                            ])->first();
        $data           = array(
            'id'         => $availability->id ?? "",
            'course'     => $this->getCourseResponse($availability->course, $instructorDash) ?? "",
            'booking_id' => "",
            'when_utc'   => $availability->when ?? "",
            'repeats'    => $availability->repeats ?? "",
            'is_private' => $availability->course->private ?? "",
            'status'     => empty($status)?'1':'4',
            'slots'      => $availability->slots ?? "",
            'pending'    => (string) $count //$pending_slots,
        );

        return $data;
    }

    public function getBookingAvailability($availability = '')
    {
        $data = array(
            'id'       => $availability->id ?? "",
            'when_utc' => $availability->when ?? "",
            'repeats'  => $availability->repeats ?? "",
            'slots'    => $availability->slots ?? "",
            'pending'  => "",
        );

        return $data;
    }

    /*
        repeat sessions
     */
    public function repeatSession($data, $month = null, $dayData = null, $year = null)
    {
        $days       = [];
        $time       = 'P1D';
        $dayName    = ''; //saturday
        $end        = new DateTime('last day of this month');

        /************************ set time interval for weekly, monthly and no-repeat************************/

        switch ($data['repeats']) {
            case "Daily":
                $time       = 'P1D';
                $dayName    = date('d', strtotime($data['when'])); //dayName
                $monthName  = date('m', strtotime($data['when'])); //monthname
                $getTime    = date('h:i:s', strtotime($data['when'])); //time

                if($monthName == $month) {
                    $input      = $year.'-'.$month.'-'.$dayName.' '.$getTime;
                } else {
                    $input      = $year.'-'.$month.'-01'.' '.$getTime;
                }

                $dayName    = date('Y-m-d h:i:s', strtotime($input)); //saturday
                break;
            case "Weekly":
                $time           = 'P7D';
                $dayName        = date('d', strtotime($data['when'])); //dayName

                $monthName      = date('m', strtotime($data['when'])); //monthName
                $dayWeekName    = date('l', strtotime($data['when']));
                $getTime        = date('h:i:s', strtotime($data['when'])); //time


                $getMonthName   = date('F', strtotime($year.'-'.$month.'-01'));
                $thisWeekName   = strtotime('first '.$dayWeekName.' of '.$getMonthName.' '.$year);

                if($monthName == $month) {
                    $input      = $year.'-'.$month.'-'.$dayName.' '.$getTime;
                } else {
                    $dayName    = date('d', $thisWeekName); //dayName
                    $input      = $year.'-'.$month.'-'.$dayName.' '.$getTime;
                }
                
                $dayName    = date('Y-m-d h:i:s', strtotime($input)); //saturday
                break;
            case "Monthly":
                $time       = 'P30D';
                $dayName    = date('d', strtotime($data['when'])); //saturda
                $getTime    = date('h:i:s', strtotime($data['when'])); //time
                $input      = $year.'-'.$month.'-'.$dayName.' '.$getTime;

                $dayName    = date('Y-m-d h:i:s', strtotime($input)); //saturday
                break;
            case "No Repeat":
                $time       = 'P1D';
                $dayName    = date('d', strtotime($data['when']));
                $getTime    = date('h:i:s', strtotime($data['when'])); //time
                $input      = $year.'-'.$month.'-'.$dayName.' '.$getTime;
                $dayName    = date('Y-m-d h:i:s', strtotime($input)); //saturday
                break;
            default:
                $date_calculation = "none";
        }
        /************************ end of time interval for weekly, monthly and no-repeat************************/

        /************************ set end time for date, month, year and no-repeat ************************/
        if($data['repeats'] == 'No Repeat') {
            $dateWhen   = date('Y-m-d', strtotime($data['when']));
            $end        = date("Y-m-d", strtotime($dateWhen));
            $end        = new DateTime($end);
            $end        = $end->modify( '+1 day' );
        }
        elseif(!empty($dayData)) {
            $input      = $year.'-'.$month.'-'.$dayData;
            $end        = date("Y-m-d", strtotime($input));
            $end        = new DateTime($end);
            $end        = $end->modify( '+1 day' );
        }
        elseif(!empty($month) && !empty($year)) {
            $input  = $year.'-'.$month.'-01';
            $end    = date("Y-m-t", strtotime($input));
            $end    = new DateTime($end);
        }
        /************************ end of set end time for date, month, year and no-repeat ************************/

        //$end = new DateTime('last day of this month');
        //$end = $end->modify( '+1 day' );     // new line
        if(!empty($dayName)) {
            $period = new DatePeriod(
                new DateTime($dayName), // Start date of the period
                new DateInterval($time), // Define the intervals as Periods of $time Day
                $end, // Apply the interval $end times on top of the starting date
            );

            $courseAvail    = CourseAvailability::where('parent_id', $data['id'])->first();
            $childStartDate = $courseAvail->start_date ?? "";

            foreach ($period as $day)
            {
                //checking for cancelled session in between
                if($day->format('Y-m-d h:i:s') == $data['end_date'] && $day->format('Y-m-d h:i:s') == $childStartDate) {
                    continue;
                }

                //checking for last date
                elseif($day->format('Y-m-d h:i:s') == $data['end_date'] && empty($childStartDate)) {
                    break;
                }

                $days[]     = array(
                    'id'       => $data['id'] ?? "",
                    'when_utc' => $day->format('Y-m-d h:i:s') ?? "",
                    'repeats'  => $data['repeats'] ?? "",
                    'slots'    => $data['slots'] ?? "",
                    'pending'  => "",
                );
            }
        }
        return $days;
    }

    public function getAvailability($availability = '', $month = '', $day = '', $year = '', $course = '')
    {
        $data = array();
            //dd($availability);
            //dd(Auth::user());
        foreach ($availability as $key => $value) {
            if(!empty($month) || !empty($year)) {
                $data[]    = $this->repeatSession($value, $month, $day, $year);
            } else {

                list($dayName, $time, $end)  = $this->getDatePeriodData($value);
         
                $period         = new DatePeriod(
                    new DateTime($dayName), // Start date of the period
                    new DateInterval($time), // Define the intervals as Periods of $time Day
                    $end, // Apply the interval $end times on top of the starting date
                );

                foreach ($period as $day)
                {                    
                    $inArray = [];
                    $count = 0;
                    if(!empty($course)) {
                        if($course->bookings->isNotEmpty()) {
                            $bookings = $course->bookings;
                            foreach($bookings as $bookValue) {
                                if($bookValue->when == $value->when) {
                                    $count += 1;
                                } 
                            }
                        }
                    }

                    $data[]     = array(
                        'id'       => $value->id ?? "",
                        'when_utc' => $day->format('Y-m-d h:i:s') ?? "",
                        'repeats'  => $value->repeats ?? "",
                        'slots'    => $value->slots ?? "",
                        'pending'  => (string) $count,
                    );

                    break;
                }
            }
            
        }
        if(!empty($month) && !empty($year)) {
            $single = array_reduce($data, 'array_merge', array());
        }

        return $single ?? $data;
    }


    //get data for periods without passing month, year and date for cal Slots
    public function getDatePeriodData($value='')
    {
        $monthName      = date('m', strtotime($value->when)); //monthname
        $dateName       = date('d', strtotime($value->when)); //dayName
        $yearName       = date('Y', strtotime($value->when)); //dayName
        $getTime        = date('h:i:s', strtotime($value->when)); //time
        $dayWeekName    = date('l', strtotime($value->when));
        $getMonthName   = date('F', strtotime(date("Y").'-'.date("m").'-01'));

        if($monthName == date("m")) {
            $input      = $yearName.'-'.$monthName.'-'.$dateName.' '.$getTime;
        } else {
            $input      = date("Y").'-'.date("m").'-01'.' '.$getTime;
        }

        $dayName    = date('Y-m-d h:i:s', strtotime($input)); //saturday
        switch ($value->repeats) {
            case "Daily":
                $time       = 'P1D';

                if($monthName == date("m")) {
                    $input      = $yearName.'-'.$monthName.'-'.$dateName.' '.$getTime;
                } else {
                    $advanceDate    = date('d', strtotime("+1 day"));
                    $input          = date("Y").'-'.date("m").'-'.$advanceDate.' '.$getTime;
                }

                $dayName    = date('Y-m-d h:i:s', strtotime($input)); //saturday

                break;
            case "Weekly":
                $time       = 'P7D';

                /*if($monthName == date("m")) {
                    $input      = $yearName.'-'.$monthName.'-'.$dateName.' '.$getTime;
                } else {*/
                $thisWeekName   = strtotime('first '.$dayWeekName.' of '.$getMonthName.' '.date("Y"));
                $dayName        = date('d', $thisWeekName); //dayName

                if($dayName <= date("d")) {
                    $thisWeekName   = strtotime('second '.$dayWeekName.' of '.$getMonthName.' '.date("Y"));
                    $dayName        = date('d', $thisWeekName); //dayName
                }

                $input          = date("Y").'-'.date("m").'-'.$dayName.' '.$getTime;
                /*}*/
                $dayName    = date('Y-m-d h:i:s', strtotime($input)); //saturday
                break;
            case "Monthly":
                $time       = 'P30D';

                if($dateName >= date("d")) {
                    $input          = date("Y").'-'.date("m").'-'.$dateName.' '.$getTime;

                } else {
                    $advanceDate    = date('m', strtotime("+1 month"));
                    $input          = date("Y").'-'.$advanceDate.'-'.$dateName.' '.$getTime;
                }

                $dayName        = date('Y-m-d h:i:s', strtotime($input)); //saturday
                break;
            case "No Repeat":
                $time       = 'P1D';
                break;
            default:
                $time       = 'P1D';
        }


        //$input      = date("Y").'-'.date("m").'-'.$dateName.' '.$getTime;
        $end        = date("Y-m-d h:i:s", strtotime($input));
        $end        = new DateTime($end);
        $end        = $end->modify( '+1 day' );

        return [$dayName, $time, $end];
    }

    public function getInstructor($id = '')
    {
        $host = request()->getSchemeAndHttpHost();
        $getCurrentUser         = Auth::user();
        $users                  = User::find($id);

        $category_id = [];
        if (isset($users->categories)) {
            foreach ($users->categories as $k => $categoryValue) {
                $category_id[] = (string) $categoryValue->category->name;
            }
        }

        $image                  = $this->getMetaValue($users, 'image');
        $data['id']             = $users->id;
        $data['name']           = $users->first_name;
        $data['average']        = (string) round($users->getAverageRatingAttribute($users->id), 2);
        $data['categories']     = $category_id;
        $data['count']          = (string) $users->getCountRatings($users->id);
        $data['is_fav']         = $getCurrentUser->getInstructor($users->id);
        $data['image']          = !empty($image) ? $host . '/' . $image : "";

        return $data;
    }

    public function getSavedCategories($value = '')
    {
        $host  = request()->getSchemeAndHttpHost();
        $image = $value->category->image;

        $groupCategories['id']    = $value->category->id;
        $groupCategories['name']  = $value->category->name;
        $groupCategories['image'] = !empty($image) ? $host . '/' . $image : "";

        return $groupCategories;
    }

    /*
    Response for Card response
     */
    public function getCardDetails($card='')
    {
        $data = array();
        foreach ($card as $key => $value) {
            $data[] = array(
                'id'            => $value->id,
                'last'          => $value->last,
                'type'          => $value->type,
                'name'          => $value->name,
                'primary'       => $value->primary,
                'customer_id'   => $value->customer_id,
            );
        }

        return $data;
    }

    public function getPaymentMethodDetails($PaymentMethod = '')
    {
        $data = array();
        foreach ($PaymentMethod as $key => $value) {
            if ($value->payment_type == "0" || $value->payment_type == "card" ) {
                $data['cards'][] = array(
                    'id'             => $value->id,
                    'stripe_cust_id' => $value->stripe_cust_id,
                    'title'          => $value->title,
                    'ending'         => $value->ending,
                    'type'           => $value->type,
                );
            } else {
                $data['paypal'] = array(
                    'id'            => $value->id,
                    'paypal_email'  => $value->paypal_email,
                    'authorization' => $value->authorization,
                );
            }
        }

        return $data;
    }

    // function for login response with token
    public function evaluateRespondDataWithToken($users = '', $token, $profile = false)
    {

        $categories      = $users->categories;
        $groupCategories = [];
        foreach ($categories as $key => $value) {
            $groupCategories[] = $this->getSavedCategories($value);
        }

        $first_name       = $users->first_name;
        $last_name        = $users->last_name;
        $phone            = $users->phone;
        $image            = $this->getMetaValue($users, 'image');
        $email            = $users->email;
        $login_type       = $users->login_type;
        $account_type     = (string) $users->account_type;
        $educations       = $users->education;
        $experiences      = $users->experience;
        $certificate      = $users->certificate;

        $getExperience  = $this->getExperience($experiences); //getting experience structure
        $getEducation   = $this->getEducation($educations); //getting educations structure
        $getCertificate = $this->getCertificate($certificate); //getting certificate structure

        $course             = $users->course;
        $about              = $this->getMetaValue($users, 'about'); //getting about from user details table
        $paypal             = $this->getMetaValue($users, 'paypal'); //getting about from user details table
        $xoom               = $this->getMetaValue($users, 'xoom'); //getting about from user details table
        $stripe_connect_id  = $this->getMetaValue($users, 'stripe_connect_id'); //getting stripe_connect_id from user details table
        $stripe_connect_email = $this->getMetaValue($users, 'stripe_connect_email'); //getting stripe_connect_email from user details table
        $tag_line         = $this->getMetaValue($users, 'tag_line'); //getting tag_line from user details table
        $fcm_token        = $this->getMetaValue($users, 'fcm_token'); //fcm_token
        $pushNotification = $users->pushNotifications->status ?? 0; //pushnotif

        $settings           = [];
        $getSetting         = Setting::get();
        foreach ($getSetting as $keySetting => $valueSetting) {
            $settings[] = array(
                                'name' => $valueSetting->name,
                                'value' => $valueSetting->value
                        );
        }

        $getCourse = array();
        foreach ($course as $key => $value) {
            $getCourse[] = $this->getCourseResponse($value); //getting Course structure
        }

        /*$email_verified = $this->getMetaValue($users, 'email_verified');
        $phone_verified = $this->getMetaValue($users, 'phone_verified');*/
        $host = request()->getSchemeAndHttpHost();

        $expires_in = Auth::factory()->getTTL() * 60;
        $data       = array(
            'token'                => $token,
            'token_type'           => 'bearer',
            'expires_in'           => (string) $expires_in,
            'id'                   => (string) $users->id,
            'first_name'           => $first_name ?? "",
            'last_name'            => $last_name ?? "",
            'email'                => $email ?? "",
            'login_type'           => $login_type??"",
            'phone'                => $phone ?? "",
            'account_type'         => $account_type ?? "",
            'push_notification'    => (string) $pushNotification ?? "0",
            'fcm_token'            => $fcm_token ?? '',
            'interests'            => $groupCategories ?? "",
            'paypal'               => $paypal ?? "",
            'xoom'                 => $xoom ?? "",
            'education'            => $getEducation,
            'experience'           => $getExperience,
            'courses'              => $getCourse,
            'certificate'          => $getCertificate,
            'about'                => $about ?? "",
            'settings'             => $settings,
            'tag_line'             => $tag_line ?? "",
            //'tip_percent'          => $tip_percent ?? "10",
            'stripe_connect_id'    => $stripe_connect_id ?? "",
            'stripe_connect_email' => $stripe_connect_email ?? "",
            //'verified'             => $verified,
            'image'                => !empty($image) ? $host . '/' . $image : "",
            'ratings'              => "",
        );

        return $data;
    }

    //simple response without token
    public function evaluateRespondData($users = '')
    {
        $first_name = $users->first_name;
        $last_name  = $users->last_name;
        $phone      = $this->getMetaValue($users, 'phone');

        $data = array(
            'user_id'    => (string) $users->id,
            'first_name' => $first_name ?? "null",
            'last_name'  => $last_name ?? "null",
            'phone'      => $users->phone ?? "null",
            'email'      => $users->email ?? "null",
        );

        return $data;
    }

    //..saving notification
    public function saveNotification($id, $title, $message, $from_id = null, $order_id = null)
    {
        $users = User::find($id);
        try {
            Notification::create([
                'user_id' => $id,
                'from_id' => $from_id,
                'title'   => $title ?? '',
                'message' => $message,
                'status'  => 0, //when someone make request on going on run
            ]);
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }

        if ($users->pushNotifications) {
            if ($users->pushNotifications->status == '0') {
                return false;
            }
        }
        //dd($users->pushNotifications->status);
        $firebaseToken[] = $this->getMetaValue($users, 'fcm_token'); //getting firebase token

        $SERVER_API_KEY = 'AAAA7LE7ObI:APA91bHawUIeBUn-ek6S5XO8d4aNjMbmeHJXsAhxnoiBVdOd63a-c0tc3l6O2Kv48l3CeNaIA8uGScLnyRQrqESdIvxjNMDlHqPw0xVKyc6lACO4BP8ZnO0eD-__uJma3ieskVxaUsFy';
        $Itoken         = array_unique($firebaseToken);
        $Itoken         = array_values($Itoken);

        $data = [
            "registration_ids"  => $Itoken,
            "content_available" => true,
            "mutable_content"   => true,
            "priority"          => "high",
            "notification"      => [
                "title" => $title,
                "body"  => $message,
                "sound" => "default",
            ],
            "data"              => [
                "order_id" => $order_id ?? "",
                "user_id"  => $users->id ?? "",
            ],
        ];
        $dataString = json_encode($data);

        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

        $response = curl_exec($ch);

        if ($response === false) {
            return $this->responseWithError('FCM Send Error: ' . curl_error($ch), 402);
        }

        return $response;
    }

    //splitting expiry date
    public function split_expiry($expiry)
    {
        $exp = trim($expiry);
        $var = preg_split("#/#", $exp);
        return $var;
    }

    public function invenDescSort($item1 = '', $item2 = '')
    {
        if ($item1['out_of_stock'] == $item2['out_of_stock']) {
            return 0;
        }

        return ($item1['out_of_stock'] > $item2['out_of_stock']) ? 1 : -1;
    }

    public function customPagination($data = '')
    {
        // Get current page form url e.x. &page=1
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        // Create a new Laravel collection from the array data
        $wineCollection = collect($data);
        // Define how many products we want to be visible in each page
        $perPage = 20;
        // Slice the collection to get the products to display in current page
        $currentPageproducts = $wineCollection->slice(($currentPage * $perPage) - $perPage, $perPage)->all();
        // Create our paginator and pass it to the view
        $data = new LengthAwarePaginator($currentPageproducts, count($wineCollection), $perPage);
        // set url path for generted links
        return $data->setPath(request()->url());
    }

    public function generateRandomString($length = 13)
    {
        $characters       = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString     = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function clean($string)
    {
        return preg_replace('/[^a-zA-Z0-9_ -]/s', '', $string);
    }


    public function filters($courses)
    {
        $keyword = request()->input('keyword');
        if (request()->input('keyword')) {
            $courses->where(function ($query) use ($keyword) {
                $query->where('title', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('description', 'LIKE', '%' . $keyword . '%');

                $query->orWhere(function ($subQuery) use ($keyword) {
                    $subQuery->whereHas('users', function ($q) use($keyword) {
                        $q->where(function ($subQuery2) use ($keyword) {
                            $subQuery2->where('first_name', 'LIKE', '%' . $keyword . '%')
                            ->orWhere('last_name', 'LIKE', '%' . $keyword . '%');
                        });
                    });
                });
                $query->orWhere(function ($subQuery) use ($keyword) {
                    $subQuery->whereHas('tags', function ($q) use($keyword) {
                        $q->where(function ($subQuery3) use ($keyword) {
                            $subQuery3->where('tag', 'LIKE', '%' . $keyword . '%');
                        }
                        );
                    });
                });

            });
        }

        /************************** languages ****************************************/
        $languages = request()->input('languages');
        $courses->when($languages, function($q) use($languages) {
            $q->whereHas('languages', function ($qLanguage) use($languages) {
                $qLanguage->whereIn('language_id', $languages);
            });
        });

        //filtering categories
        if (request()->input('categories')) {
            $courses->whereHas('categories', function ($q) {
                $q->whereIn('categories_id', request()->input('categories'));
            });
        }

        //filtering date time
        if (request()->input('start_time') && request()->input('end_time')) {
            $courses->whereHas('avail', function ($q) {
                $q->whereBetween('minutes', [request()->input('start_time'), request()->input('end_time')]);
            });
        }

        //filtering level
        if (request()->input('level')) {
            $courses->whereHas('levels', function ($q) {
                $q->whereIn('level_id', request()->input('level'));
            });
        }
        /************************** end of filter ****************************************/

        $allCourses = $courses->get();
        return $allCourses;
    }

    public function sendPushCrul($data){
        //$SERVER_API_KEY = env('SERVER_FIREBASE_KEY');
        $SERVER_API_KEY ='AAAAa-d66zw:APA91bEMasyMLFphook4sULMMY8OjOdnFrfSEn4ejrAA6GM4GAKz54D9DYHW7we-TQ4AkqNHVyj6SbnZJABrlwHPHvF0hvZUk1GwNZlHAaQlnnCIjJqSiJ3ucWsIyDSMlpOS2Xmu48k8';
        $dataString = json_encode($data);
        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

        $response = curl_exec($ch);
        if ($response === false) {
            return $this->responseWithError('FCM Send Error: ' . curl_error($ch), 402);
        }
        return $response;
    }

    public function getAvailabilityUnix($availability, $from_utc, $to_utc){
        $avalibilty     =   [];
        $endDate        =   date('Ymd', $to_utc). 'T'.date('His', $to_utc);
        foreach($availability as $courseAvail){            
            // Get all booking of current availability
            $startDate  =   date('Y-m-d',$from_utc).' '.date('H:i:s', strtotime($courseAvail->when));
            $bookingCountArray  =     Booking::select('when',\DB::raw('count(*) as used_slots'))
                                ->where('availability_id',$courseAvail->id)
                                ->where('status',1)
                                ->groupBy('when')->get()->keyBy('when');
            //if no repeats then No occurrences  
            // this check If  occurrences is terminate by user
            if(!empty($courseAvail->end_date)){
                if(date('Y-m-d', strtotime($courseAvail->end_date)) < date('Y-m-d', $to_utc)){
                        $endDate  = date('Ymd', strtotime($courseAvail->end_date)). 'T'.date('His', strtotime($courseAvail->end_date));
                }
            }
            // Create all exclusion Strings 
           /* $exclusion ='';
            if(!empty($courseAvail->availabilityExclusion)){
                $exclusion = implode(',',$courseAvail->availabilityExclusion->pluck('exclusions')->toArray());
            }*/
            $exclusion=[];
            if(!empty($courseAvail->availabilityExclusion)){
                $exclusion = $courseAvail->availabilityExclusion->pluck('exclusions')->toArray();
            }          
            if($courseAvail->rule == 'FREQ=NO REPEAT'){
                if(date('Y-m-d', strtotime($courseAvail->when)) >= date('Y-m-d', $from_utc)  && date('Y-m-d', strtotime($courseAvail->when)) <= date('Y-m-d', $to_utc) ){
                    $avalibiltyObj =  new  \stdClass();
                    $when_utc                   =   $courseAvail->when;
                    $avalibiltyObj->id          =   $courseAvail->id;
                    $avalibiltyObj->when_utc    =   $when_utc;
                    $avalibiltyObj->when_utc_unix   =   strtotime($when_utc);
                    $avalibiltyObj->repeats     =   $courseAvail->repeats;
                    $avalibiltyObj->slots       =   $courseAvail->slots;
                    $avalibiltyObj->status      =   in_array($when_utc,$exclusion)?4:1;
                    $avalibiltyObj->pending     =   !empty($bookingCountArray[$when_utc])?$bookingCountArray[$when_utc]->used_slots:0;
                    if(!($this->isCourseApi == true && $avalibiltyObj->status ==4)){
                        $avalibilty[]               =   $avalibiltyObj;
                    }
                    
                }
            }else{

                //check if getting same month availability when it creats                     
                if(  date('Y-m-d', strtotime($courseAvail->when)) >= date('Y-m-d', $to_utc) ){
                    $startDate = $courseAvail->when; 
                }
                if($courseAvail->rule == 'FREQ=WEEKLY'){    
                    // get day in String 'SU,MO....'        
                    $day  = Carbon::create($courseAvail->when)->format('l');
                    $day  = strtoupper(substr($day,0,2));
                    $rule = $courseAvail->rule.';BYDAY='.$day.';UNTIL='.$endDate;
                }else{
                    $rule  =  $courseAvail->rule.';UNTIL='.$endDate;
                }
                $r = new When();
                $r->RFC5545_COMPLIANT = When::IGNORE;
                $r->startDate(new DateTime($startDate))
                ->rrule($rule)
                //->exclusions($exclusion)
                  ->generateOccurrences();
                foreach($r->occurrences as $occurrences){
                    $avalibiltyObj =  new  \stdClass();
                    $when_utc                   =   $occurrences->format('Y-m-d H:i:s');
                    $avalibiltyObj->id          =   $courseAvail->id;
                    $avalibiltyObj->when_utc    =   $when_utc;
                    $avalibiltyObj->when_utc_unix   =   strtotime($when_utc);
                    $avalibiltyObj->repeats     =   $courseAvail->repeats;
                    $avalibiltyObj->slots       =   $courseAvail->slots;
                    $avalibiltyObj->status      =   in_array($when_utc,$exclusion)?4:1;
                    $avalibiltyObj->pending     =   !empty($bookingCountArray[$when_utc])?$bookingCountArray[$when_utc]->used_slots:0;

                                                    //$this->getSlots($when_utc,$courseAvail->id,
                                                    //$courseAvail->slots);//$pending_slots;
                    if(!($this->isCourseApi == true && $avalibiltyObj->status ==4)){
                        $avalibilty[]               =   $avalibiltyObj;
                    }
                }
            }    
        }
        return $avalibilty;        
    }
}
