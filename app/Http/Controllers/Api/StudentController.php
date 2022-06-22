<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Course;
use App\Models\FavCourse;
use App\Models\FavInstructor;
use App\Models\User;
use App\Models\PushToken;
use App\Models\CourseAvailability;
use App\Models\Notification;
use App\Traits\Meta;
use DateTime;
use DateInterval;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class StudentController extends Controller
{
    use Meta;

    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['']]);
        $this->checkConnection();
    }

    //common dashboard
    public function dashboard()
    {
        $validate = Validator::make(request()->all(), [
            'user_id' => 'required',
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }
        //#1
        $getAllCategories = Category::inRandomOrder()->limit(7)->get();
        //#2
        $users           = User::find(Auth::user()->id);
        $categories      = $users->categories;
        $groupCategories = [];
        foreach ($categories as $key => $value) {
            $groupCategories[] = $value->category->id; //$this->getSavedCategories($value);
        }

        $allCourses = Course::getPublicSession()->inRandomOrder()->limit(7)->get();
        //get all courses
        $check = array();

        foreach ($allCourses as $key => $value) {
            $flag = 0;
            foreach ($value->categories as $keyCat => $valCat) {
                //dd($value->categories);
                if (in_array($valCat->categories_id, $groupCategories)) {
                    $flag = 1;
                }
            }
            if ($flag == 1) {

                $CourseDetails = $this->getCourseResponse($value);
                if (empty($CourseDetails['availability']))
                continue;
                $check[] = $CourseDetails;
            }
        }

        if (empty($check)) {
            $allCourses = Course::getPublicSession()->inRandomOrder()->limit(5)->get();
            foreach ($allCourses as $key => $value) {
                $CourseDetails = $this->getCourseResponse($value);
                if (empty($CourseDetails['availability']))
                continue;
                $check[] = $CourseDetails;
            }
        }

        //#3
        //$courses = Course::where('featured', 1)->get();
        $courses = Course::getPublicSession()->inRandomOrder()->limit(7)->get();
        //get all featured courses
        $getAllFeaturedCourses = array();
        foreach ($courses as $key => $value) {
            $CourseDetails = $this->getCourseResponse($value);
                if (empty($CourseDetails['availability']))
                continue;
                $getAllFeaturedCourses[] = $CourseDetails;
        }

        //#4
        $getAllUsers = User::where('account_type', 1)->inRandomOrder()->limit(7)->get();
        $instructors = array();
        foreach ($getAllUsers as $key => $users) {
            $featured = $this->getMetaValue($users, 'featured'); //fcm_token
            //if ($featured == 1) {
                $instructors[] = $this->getInstructor($users->id);
            //}
        }

        $data = array('categories' => $getAllCategories, 'interests' => $check, 'courses' => $getAllFeaturedCourses, 'instructors' => $instructors);
        return $this->responseWithDataOREmpty('Successfully Fetched', 200, $data);
    }
    //instructor apis
    public function Instructors($value = '')
    {
        $validate = Validator::make(request()->all(), [
            'user_id' => 'required',
            'type'    => 'required',
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }
        $data        = array();
        $getAllUsers = User::where('account_type', 1);
        //keyword search
        $keyword = request()->input('keyword') ?? "";
        if (!empty($keyword)) {
            $getAllUsers->where(function ($query) use ($keyword) {
                $query->where('first_name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('last_name', 'LIKE', '%' . $keyword . '%');
            });
        }

        $getAllUsers = $getAllUsers->get();

        $intructors = array();
        foreach ($getAllUsers as $key => $users) {
            $featured = $this->getMetaValue($users, 'featured'); //fcm_token
            //type search
            $type = request()->input('type');
            if ($type == 1) {
                if ($featured == 1) {
                    $intructors[] = $this->getInstructor($users->id);
                }
            } else {
                $intructors[] = $this->getInstructor($users->id);
            }
        }

        return $this->responseWithDataOREmpty('Successfully Fetched', 200, $intructors);
    }

    //filter course
    public function FilterCourses()
    {
        $validate = Validator::make(request()->all(), [
            'user_id' => 'required',
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }
        
        $keyword = request()->input('keyword') ?? "";

        $course = Course::getPublicSession();
        //$course    = $course->select('id', 'user_id', 'title', 'cover', 'price', 'description', 'private', 'featured', 'price_to_you', 'price_to_student', 'level', 'created_at');

        // return $this->responseWithDataOREmpty("message", 200, $course->get());

        //keyword search
        if (request()->input('keyword')) {
            $course->where(function ($query) use ($keyword) {
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

            });
        }

        //filtering languages
        //if (request()->input('languages')) {
            $languages = request()->input('languages');
            $course->when($languages, function($q) use($languages) {
                $q->whereHas('languages', function ($qLanguage) use($languages) {
                    $qLanguage->whereIn('language_id', $languages);
                });
            });
       // }

        //filtering categories
        if (request()->input('categories')) {
            $course->whereHas('categories', function ($q) {
                $q->whereIn('categories_id', request()->input('categories'));
            });
        }

        //filtering date time
        if (request()->input('start_time') && request()->input('end_time')) {
            $course->whereHas('avail', function ($q) {
                $q->whereBetween('when', [request()->input('start_time'), request()->input('end_time')]);
            });
        }

        //filtering level
        if (request()->input('level')) {
            $course->whereHas('levels', function ($q) {
                $q->whereIn('level_id', request()->input('level'));
            });
        }

        $course = $course->get();
        
        $data   = array();
        foreach ($course as $key => $value) {
            $data[] = $this->getCourseResponse($value);
        }

        $message = 'Successfully fetched';
        if (empty($data)) {
            $message = 'No Course Found';
        }

        return $this->responseWithDataOREmpty($message, 200, $data);
    }

    //courses api
    public function Courses(Request $request)
    {
        $validate = Validator::make(request()->all(), [
            'user_id' => 'required',
            'type'    => 'required',
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        $data         = array();
        $setTimeArray = array();
        $getRequest = request()->all();
        $from = request()->input('from') ?? "";
        $to = request()->input('to') ?? "";
        $course = Course::query();
        if(!empty($from) && !empty($to)){
           $course->withWhereHas('avail', function ($query) use ($getRequest) {
                $query->where(function ($queryWhere) use ($getRequest){
                    $queryWhere->whereIn('repeats',['Daily','Weekly'])
                                ->whereBetween('minutes',[$getRequest['from'],$getRequest['to']]);
                });
                $query->orWhere(function ($queryWhereRepeat) use ($getRequest){
                    $queryWhereRepeat->whereIn('repeats', ['No Repeat'])
                        ->where('when','>',date('Y-m-d'))
                            ->whereBetween('minutes',[$getRequest['from'],$getRequest['to']]);
                });
            });
        }
        
        $languages = 1;

        // return $this->responseWithDataOREmpty("sada", 200, $course);


        //keyword search
        $keyword = request()->input('keyword') ?? "";
        if (!empty($keyword)) {
            $course->where(function ($query) use ($keyword) {
                $query->where('title', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('description', 'LIKE', '%' . $keyword . '%');
            });
        }
    
        //type search
        $type = request()->input('type');
        if ($type == 1) {
            $course->where('featured', 1);
            $course->where('private','!=',1);
        } elseif ($type == 3) {   //type 3 own user get courses
            $course->where('user_id', Auth::user()->id);
            //$course = $course->groupBy('created_at');
        }
        else{
            $course->where('private','!=',1);
        }
        $course->where('deleted','!=',1);
        $course = $course->get();
        $isCourseApi = true;
        foreach ($course as $key => $value) {
            /******************** languages filter ***********************************/
            if (request()->input('languages')) {
                $languages              = 0;
                $currentCourseLanguages = $this->getLanguages($value->languages);
                foreach (request()->input('languages') as $keyLanguage => $valLanguage) {
                    if (in_array($valLanguage, $currentCourseLanguages)) {
                        $languages = 1;
                    }
                }
            }
            /************************* end of languages filter ***********************/

            /************type 2 Courses of Interests filter**********/
            $CourseDetails = $this->getCourseResponse($value ,'',$isCourseApi);
            if (empty($CourseDetails['availability']) && request()->input('type') != 3)
            continue;

            if ($type == 2) {
                $flag = 0;
                foreach ($value->categories as $keyCat => $valCat) {
                    if (in_array($valCat->categories_id, $this->getAllInterests())) {
                        $flag = 1;
                    }
                }
                if ($flag == 1 && $languages == 1) {
                    $data[] = $CourseDetails; //$this->getCourseResponse($value,'',$isCourseApi);
                }
            } else {
                if ($languages == 1) {
                    if ($type == 3) {
                        //$CourseDetails = $this->getCourseResponse($value ,'',$isCourseApi);
                        if(!empty($CourseDetails['availability'])){
                            $setTimeArray[date("F Y",time())][] = $CourseDetails;
                        }
                        else{
                           // dd($value->avail[0]->when);
                            //if($value->avail[0]->when)
                            if(isset($value->avail[0]->when))
                            $setTimeArray[date("F Y",strtotime($value->avail[0]->when))][] = $CourseDetails;
                        }
                    } else {
                        $data[] = $CourseDetails;//$this->getCourseResponse($value ,'',$isCourseApi);
                    }
                }
            }
        }
        foreach ($setTimeArray as $keyValue => $secondValue) {
            $data[] = array(
                'month_name' => $keyValue,
                'courses'    => $setTimeArray[$keyValue],
            );
        }
        $message = 'Successfully fetched';
        if (empty($data)) {
            $message = 'No Course Found';
        }

        return $this->responseWithDataOREmpty($message, 200, $data);
    }

    //course details
    public function CourseDetails($value = '')
    {
        $validate = Validator::make(request()->all(), [
            'user_id'   => 'required', //
            'course_id' => 'required',
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        $course = Course::find(request()->input('course_id'));
        if (!$course) {
            return $this->responseWithError('Course not found', 402);
        }

        $data = $this->getCourseResponse($course);

        return $this->responseWithDataOREmpty('Successfully Created', 200, $data);
    }

    //mark course favorite
    public function MarkCourse($value = '')
    {
        $validate = Validator::make(request()->all(), [
            'user_id'       => 'required', //
            'course_id'     => 'required',
            'favorite'      => 'required', //0/1
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        $course = Course::find(request()->input('course_id'));
        if (request()->input('favorite') == 1) {
            try {
                //save User category
                FavCourse::updateOrCreate(
                    ['user_id' => Auth::user()->id, 'course_id' => request()->input('course_id')],
                    [
                        'user_id'       => Auth::user()->id,
                        'course_id'     => request()->input('course_id'),
                    ]
                );
                $message = $course->title." marked as favorite";
            } catch (\Exception $e) {
                return $this->responseWithError($e->getMessage(), 402);
            }
        } else {
            try {
                FavCourse::where([['user_id', '=', Auth::user()->id], ['course_id', '=', request()->input('course_id')]])->delete();
                $message = $course->title." removed from favorite";
            } catch (\Exception $e) {
                return $this->responseWithError($e->getMessage(), 402);
            }
        }
        /*$data           = array();
        $FavCourse = FavCourse::get();
        foreach ($FavCourse as $key => $value) {
            $data[] = $this->getCourseResponse($value->course_id);
        }*/
        return $this->responseWithDataOREmpty($message, 200, (object) array());
    }

    //mark instructor favorite
    public function MarkInstructor($value = '')
    {
        $validate = Validator::make(request()->all(), [
            'user_id'       => 'required', //
            'instructor_id' => 'required',
            'favorite'      => 'required', //0/1
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        if (request()->input('favorite') == 1) {
            try {
                //save User category
                FavInstructor::updateOrCreate(
                    ['user_id' => Auth::user()->id, 'instructor_id' => request()->input('instructor_id')],
                    [
                        'user_id'       => Auth::user()->id,
                        'instructor_id' => request()->input('instructor_id'),
                    ]
                );
                $message = "Instructor marked as favorite";
            } catch (\Exception $e) {
                return $this->responseWithError($e->getMessage(), 402);
            }
        } else {
            try {
                FavInstructor::where([['user_id', '=', Auth::user()->id], ['instructor_id', '=', request()->input('instructor_id')]])->delete();
                $message = "Instructor removed from favorite";
            } catch (\Exception $e) {
                return $this->responseWithError($e->getMessage(), 402);
            }
        }
        $data           = array();
        $FavInstructors = FavInstructor::get();
        foreach ($FavInstructors as $key => $value) {
            $data[] = $this->getInstructor($value->instructor_id);
        }
        return $this->responseWithDataOREmpty($message, 200, (object) array());
    }

    //favorite instructor
    public function myFavorite()
    {
        $validate = Validator::make(request()->all(), [
            'user_id' => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        $data           = array();
        $FavInstructors = FavInstructor::where('user_id', Auth::user()->id)->get();
        $FavCourse      = FavCourse::where('user_id', Auth::user()->id)->get();

        //getCourse api
        $getCourses = array();
        foreach ($FavCourse as $key => $value) {
            $course = Course::find($value->course_id);
            $getCourses[] = $this->getCourseResponse($course);
        }

        //getInstructor api
        $intructors  = array();
        foreach ($FavInstructors as $key => $value) {
            $intructors[] = $this->getInstructor($value->instructor_id); //getting instructor data
        }

        $data['course']     = $getCourses;
        $data['instructor'] = $intructors;

        return $this->responseWithDataOREmpty('Successfully fetched', 200, $data);
    }

    //Create booking
    public function CreateBooking(Request $request)
    {
        $validate = Validator::make(request()->all(), [
            'user_id'         => 'required', //
            'course_id'       => 'required', //
            'availability_id' => 'required', //
            'when'            => 'required', //
            'sessions'        => 'required', //
            'email'           => 'required', //
            'phone'           => 'required', //
            'payment_method'  => 'required', //
            'transaction_id'  => 'required', //
            'amount'          => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        $getCourseInformation = CourseAvailability::where("id","=",$request->availability_id)->get();
        //get day month and year from when date

        $getDate = new DateTime($request->when);
        
        if(count($getCourseInformation) > 0){
            $courseInfo = $getCourseInformation[0];
            $slots = $courseInfo['slots'];
            $repeats = $courseInfo['repeats'];

              if($repeats == "Daily"){
                 $counter = 1;
              }
              else{
                $counter = 7;
              }
                $bookingAvailable = true;
                $dateNotAvailable = "";
                for($i=0; $i<$request->sessions; $i++){
                    $bookingAvailabilty = $this->checkBooking($request,$i, $counter, $slots);
                    if(strlen($bookingAvailabilty) > 0){
                        $bookingAvailable = false;
                        $dateNotAvailable = $bookingAvailabilty;
                        break;
                    }
                }

                if($bookingAvailable == true){
                   for($i=0; $i<$request->sessions; $i++){
                        $booked = $this->bookSession($request,$i, $counter, $slots); 
                        
                        if( $booked == -1){
                          return $this->responseWithError('Course not found', 402);
                        }
                        $booking = Booking::find($booked);
                        $data[] = $this->getBookingResponse($booking);
                   
                    }
                        $msg = $booking->users->first_name.' '.$booking->users->last_name.' has booked a session for  '.$booking->course->title.' on date '.date('Y-m-d',strtotime($booking->when)).' at '.date('H:i',strtotime($booking->when));

                        $this->saveNotifications($booking->users,$booking->course->user_id,$booking->course_id,$booking->availability_id,$msg,$booking->when);
                        $instructor_id  =   $booking->course->user_id ?? null;
                        $firebaseToken  =   PushToken::where('user_id',$instructor_id)
                                            ->pluck('token')->toArray(); 
                        $Itoken         =   array_values($firebaseToken);
                        $pushData = [
                            "registration_ids"  => $Itoken,
                            "content_available" => true,
                            "mutable_content"   => true,
                            "priority"          => "high",
                            "notification"      => [
                                "title" => 'Session Booked',
                                "body"  => $msg,
                                "sound" => "default",
                            ],
                            "data"              => [
                                "availability_id" => request()->input('availability_id') ?? "",
                            ],
                        ];
                        $this->sendPushCrul($pushData);
                       return $this->responseWithDataOREmpty('Successfully Booked', 200, $data);

                }
                else{
                    return $this->responseWithError('Unfortunately one of the session is not available on '.$dateNotAvailable.' Please try to schedule on another dates.', 402);
                }

        }
        else{  //else for availabilty id not found
            return $this->responseWithError('Availability not found', 402);
        }
    }


    public function checkBooking($request,$i ,$counter, $slots){
        $getDate = new DateTime($request->when);
        $sessionStartDateAndTime = new DateTime($getDate->format('Y-m-d'));
        $sessionDateAndTime = $sessionStartDateAndTime->add(new DateInterval("P".($counter*$i)."D")); // PD Period of time
        $checkAvailability = $this->checkSlotDateAvailability($request, $sessionDateAndTime);        
        if($checkAvailability >= $slots){
            $date = $sessionDateAndTime->format("Y-m-d");
            return $date;
        }
        return "";
    } 

    public function bookSession($request,$i ,$counter, $slots){
        $getDate = new DateTime($request->when);
        $sessionStartDateAndTime = new DateTime($getDate->format('Y-m-d'));
        $sessionDateAndTime = $sessionStartDateAndTime->add(new DateInterval("P".($counter*$i)."D")); // PD Period of time

            $date = $sessionDateAndTime->format('d');
            $month = $sessionDateAndTime->format('m');
            $year = $sessionDateAndTime->format('Y');
            $fullYear = $sessionDateAndTime->format('Y-m-d');
            $fullTime = $getDate->format('H:i:s');

            $booking = new Booking;
            $booking->user_id = $request->user_id;
            $booking->course_id = $request->course_id;
            $booking->availability_id = $request->availability_id;
            $booking->when = $fullYear." ".$fullTime;
            $booking->date = $date;
            $booking->month = $month;
            $booking->year = $year;
            $booking->sessions = $request->sessions;
            $booking->email = $request->email;
            $booking->phone = $request->phone;
            $booking->payment_method = $request->payment_method;
            $booking->transaction_id = $request->transaction_id;
            $booking->amount = $request->amount;
            $booking->save();
                            
            $course = Course::find(request()->input('course_id'));
            if (!$course) {
              return -1;
            }
            return $booking->id;
    }

    public function checkSlotDateAvailability($request, $getDate){
        $whereCheckForBooking = ["availability_id" => $request->availability_id, "date" =>$getDate->format("d"),"month" => $getDate->format("m"),"year" =>$getDate->format("Y")];

        $getPreExistingBookings = Booking::where($whereCheckForBooking)->get();
        return count($getPreExistingBookings);
    }

    /********** Booking history *************************/
    public function bookingHistory()
    {
        $validate = Validator::make(request()->all(), [
            'user_id' => 'required', //
            'type'    => 'required'
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }


        $booked = array();
        $data   = array();
        //type:=> student
        if (request()->input('type') === "0") {
            $booked = Booking::current()->orderby('when')->get();
            $data = $this->getAllBookingResponse($booked);
        } else {
            //type:=> teacher
            $bookedCourse = array();
            $Courses = Course::where('user_id', Auth::user()->id)->get();
            foreach ($Courses as $key => $value) {
                if (count($value->bookings) > 0) {
                    $bookedCourse[]  = $value->bookings;
                }
            }
            foreach ($bookedCourse as $key => $valueBooked) {
                $data[] = $this->getAllBookingResponse($valueBooked);
            }

            $data = call_user_func_array('array_merge', $data);
        }

        return $this->responseWithDataOREmpty('Successfully fetched booking', 200, $data);
    }
    
    //calendar view
    public function CalendarView() 
    {
        $validate = Validator::make(request()->all(), [
            'user_id' => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

      $month  = request()->input('month') ?? date('m', strtotime(Carbon::now()));
        $day    = request()->input('date')  ?? '';
        $year   = request()->input('year')  ?? date('Y', strtotime(Carbon::now()));
        $user_id = Auth::user()->id;
      /*  try {
            $bookings = Booking::query();
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }


       if ($month && $year) {
            $bookings->whereHas('availability', function ($q) use ($month, $year) {
                //$q->where(function($query) use ($month, $year) {
                $q->where([['month', '=', $month], ['year', '=', $year]]);
                $q->where(function($query) use ($month, $year) {
                    $query->where('repeats', 'No Repeat');
                });
            })->orWhereHas('availability', function ($q) use ($month, $year) {
                $q->where('repeats', 'Daily')->orWhere('repeats', 'Weekly');
            });
        }

        if ($month && $year && $day) {
            $Courses->whereHas('avail', function ($q) use ($month, $year, $day) {
                //$q->where(function($query) use ($month, $year) {
                $q->where([['month', '=', $month], ['year', '=', $year], ['day', '=', $day]]);
                $q->where(function($query) use ($month, $year) {
                    $query->where('repeats', 'No Repeat');
                });
            })->orWhereHas('avail', function ($q) use ($month, $year) {
                $q->where('repeats', 'Daily')->orWhere('repeats', 'Weekly');
            });
        } */

        //checking month whereMonth
        /*if (request()->input('month')) {
            $bookings->whereHas('availability', function ($q) use ($month) {
                $q->where('month', $month);
            });
        }

        //checking date whereDay
        if (request()->input('date')) {
            $bookings->whereHas('availability', function ($q) use ($day) {
                $q->where('day', $day);
            });
        }

        if ($year) {
            $bookings->whereHas('availability', function ($q) use ($year) {
                $q->where('year', $year);
            });
        }*/
        $startDate = Carbon::createFromFormat('d/m/Y', '01/'.$month.'/'.$year)->subDay();
        $endDate = Carbon::createFromFormat('d/m/Y', cal_days_in_month(CAL_GREGORIAN, $month, $year).'/'.$month.'/'.$year)->addDay();
        $bookings = Booking::where('user_id', $user_id)
                    ->whereBetween('when', [$startDate, $endDate])->get();
        //dd($bookings); 
        $availability = array();
        if(!empty($bookings)) {
            foreach ($bookings as $key => $booking) {
                $availability[] = $this->getCalendar($booking, $month, $day, $year);
            }
        }
        return $this->responseWithDataOREmpty('Successfully fetched', 200, $availability);
    }

    public function CalendarGroupView()
    {
        $validate = Validator::make(request()->all(), [
            'user_id' => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        $user_id = Auth::user()->id;
        try {
            $bookings = Booking::where('user_id', $user_id)->get();
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }

        $data         = array();
        $setTimeArray = array();
        foreach ($bookings as $key => $booking) {
            // code...
            $setTimeArray[$booking->created_at][] = $this->getCalendar($booking);
        }

        foreach ($setTimeArray as $keyValue => $secondValue) {
            $data[] = array(
                'month_name' => $keyValue,
                'courses'    => $setTimeArray[$keyValue],
            );
        }
        $message = 'Successfully fetched';
        if (empty($data)) {
            $message = 'No Booking Found';
        }

        return $this->responseWithDataOREmpty($message, 200, $data);
    }

    //requestCancel

    public function requestCancel(){
        $validate = Validator::make(request()->all(), [
            'user_id'   =>  'required', //
            'booking_id'=>  'required'
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        } 
        Booking::where('id',request()->input('booking_id'))->update(['status'=>3]);

        $booking        =   Booking::find(request()->input('booking_id'));
        $instructor_id  =   $booking->course->user_id ?? null;
        $msg  = $booking->users->first_name.' '.$booking->users->last_name.' has cancelled session for '.$booking->course->title.' scheduled on date '.date('Y-m-d',strtotime($booking->when)).' at '.date('H:i',strtotime($booking->when));
        $this->saveNotifications($booking->users,$booking->course->user_id,$booking->course_id,$booking->availability_id,$msg,$booking->when);
        $firebaseToken  =   PushToken::where('user_id',$instructor_id)
                            ->pluck('token')->toArray(); 
        $Itoken         =   array_values($firebaseToken);
        $data = [
            "registration_ids"  => $Itoken,
            "content_available" => true,
            "mutable_content"   => true,
            "priority"          => "high",
            "notification"      => [
                "title" => 'Session Cancelled',
                "body"  => $msg,
                "sound" => "default",
            ],
            "data"              => [
                "availability_id" => request()->input('availability_id') ?? "",
            ],
        ];
        $this->sendPushCrul($data);
        return response()->json(['message'=>'Request cancel Successfully.','status'=>200,'success'=>true], 200); 
    }
    public function saveNotifications($sender,$receiver,$course_id,$availability_id,$msg,$when){
        $notification = new Notification;
        $notification->user_id = $receiver;
        $notification->when = $when;
        $notification->availability_id = $availability_id;
        $notification->other_user_id = $sender->id;
        $notification->title = $msg;
        $notification->name = $sender->first_name.' '.$sender->last_name;
        $notification->course_id = $course_id;
        $notification->image = $sender->profile_photo_path;
        $notification->read = 0;
        $notification->save();

    }
    // get booking session student
    public function getSession(){
        $validate = Validator::make(request()->all(), [
            'user_id'           =>  'required',
            'date'             =>  'required'
        ]);
        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }
        $userBooking    = Booking::where('user_id',request()->input('user_id'))
                        ->where('when','>',request()->input('date'))->with('availability')->with('course')->get();
        
        $data = [];

        foreach($userBooking as $booking){
            if(!empty($booking->availability)&& !empty($booking->course)){
                $avalibiltyObj =  new  \stdClass();
                $avalibiltyObj->id          =   $booking->availability->id;
                $avalibiltyObj->when_utc    =   $booking->when;
                $avalibiltyObj->repeats     =   $booking->availability->repeats;
                $avalibiltyObj->rule        =   $booking->availability->rule;
                $avalibiltyObj->course_id   =   $booking->course->id;
                $avalibiltyObj->title       =   $booking->course->title;
                $avalibiltyObj->description =   $booking->course->description;
                $avalibiltyObj->session_time=   $booking->course->session_time;

                $data[]  = $avalibiltyObj;
            }
        } 
        return $this->responseWithDataOREmpty('Successfully Updated', 200, $data);
        //return  $data; 
    }

}