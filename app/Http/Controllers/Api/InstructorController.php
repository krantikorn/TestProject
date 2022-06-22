<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Certificate;
use App\Models\Booking;
use App\Models\Setting;
use App\Models\Course;
use App\Models\PushToken;
use App\Models\CourseAvailability;
use App\Models\CourseCategory;
use App\Models\CourseLanguage;
use App\Models\CourseTag;
use App\Models\CourseLevel;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Language;
use App\Models\Level;
use App\Models\RatingsToInstructor;
use App\Models\User;
use App\Models\SessionExclusion;
use App\Models\Notification;
use App\Traits\Meta;
use Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Carbon\Carbon;
class InstructorController extends Controller
{
    use Meta;
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['GetCourseMaterial']]);
        $this->checkConnection();
    }

    public function dashboard($value = '') 
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

        $courses    = array();
        $allCourses = Course::where('deleted',0)->current()->has('avail')->inRandomOrder()->get();
        $counter = 0;
        foreach ($allCourses as $key => $value) {
            $CourseDetails = $this->getCourseResponse($value);
            if (empty($CourseDetails['availability']))
            continue;
            if($counter >= 6)
            break;
            $courses[] = $CourseDetails;
            ++$counter;
        }

        $getAll       = Course::where('deleted',0)->current()->get();
        $availability = $this->getDashboardInstructor($getAll);

        $data = array('courses' => $courses, 'availability' => $availability);
        return $this->responseWithDataOREmpty('Successfully Fetched', 200, $data);
    }

    public function GetCourseMaterial()
    {
        //#1
        $categories = Category::get();

        //#2
        $languages = Language::get();

        //#3
        $level = Level::get();
        //$level = array('Beginner','Intermediate','Advance');

        $data = array('categories' => $categories, 'languages' => $languages, 'levels' => $level);
        return $this->responseWithDataOREmpty('Successfully Fetched', 200, $data);
    }


    //save payment Status
    public function savePaymentStatus()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'       => 'required',
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        $user = User::find(Auth::user()->id);
        if (!$user) {
            $this->responseWithError(self::USERNFOUND, 402);
        }
    }

    //instructor Details
    public function InstructorDetails()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'       => 'required',
            'instructor_id' => 'required',
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        $instructor_id = request()->input('instructor_id');
        // bcz sm time student bcm instructor then we got issue 
       /* try {
            User::instructor()->findOrFail($instructor_id);
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }*/ 
        $getInstructor = $this->getInstructorData($instructor_id ,true); //getting instructor data

        return $this->responseWithDataOREmpty('Successfully Saved', 200, $getInstructor);
    }

 //creating course 
    public function CreateCourse(Request $req)
    {   
        $initValidate = Validator::make(request()->all(), [
            'user_id'          => 'required', //
            'private_session'  => 'required', //
            //'is_private'       => 'required', //
        ]);

        $validateMessageInit = '';
        if ($initValidate->fails()) {
            $errorsInit = $initValidate->errors();
            foreach ($errorsInit->all() as $messageInit) {
                $validateMessageInit .= $messageInit;
            }
            return $this->responseWithError($validateMessageInit, 402);
        }

        //if private session = 0
        if (request()->input('is_private') != '' && request()->input('is_private') == 0) {
            $validate = Validator::make(request()->all(), [
                'user_id'          => 'required', //
                'title'            => 'required', //
                //'image'            => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', //
                'cover'            => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', //
                //'price'            => 'required', //
                'description'      => 'required', //
                'categories'       => 'required',
                'tags'             => 'required',
                'languages'        => 'required',
                'available_dates'  => 'required',
                'price_to_you'     => 'required',
                'price_to_student' => 'required',
                'slots'            => 'required',
                'level'            => 'required',
            ]);
        } else {
            $validate = Validator::make(request()->all(), [
                'user_id'          => 'required', //
                'title'            => 'required', //
                //'image'            => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', //
                'cover'            => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048', //
                //'price'            => 'required', //
                'description'      => 'required', //
                'available_dates'  => 'required',
                'price_to_you'     => 'required',
                'price_to_student' => 'required',
                'slots'            => 'required',
            ]);
        }

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        /*$user = User::where([['id', '=', Auth::user()->id], ['account_type', '=', '1']])->first();
        if (!$user) {
            return $this->responseWithError(self::ACCESSDENIED, 402);
        }*/

        /*************************for image*************************/
        if (request()->hasFile('image')) {
            $image           = request()->file('image');
            $image_name      = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = base_path('/public/images/courses/');
            $imagePath       = $destinationPath . "/" . $image_name;
            /*$image->move($destinationPath, $image_name);*/
            if (!$image->move($destinationPath, $image_name)) {
                $this->responseWithError('Image uploaded failed. Please try again', 402);
            }
        }
        $imageName = '';
        if (isset($image_name)) {
            $imageName = 'images/courses/' . $image_name;
        }

        /*************************for cover*************************/
        if (request()->hasFile('cover')) {
            $cover                = request()->file('cover');
            $cover_name           = time() . '.' . $cover->getClientOriginalExtension();
            $destinationPathCover = base_path('/public/images/courses/');
            $imagePathCover       = $destinationPathCover . "/" . $cover_name;
            if (!$cover->move($destinationPathCover, $cover_name)) {
                $this->responseWithError('Image uploaded failed. Please try again', 402);
            }
        }
        $coverName = '';
        if (isset($cover_name)) {
            $coverName = 'images/courses/' . $cover_name;
        }

        /*************************saving **************************************/
        $level      = request()->input('level');
        $getLevel   = explode(', ', $level);

        try {
            $course = Course::create([
                'user_id'          => Auth::user()->id,
                'title'            => request()->input('title'),
                'description'      => request()->input('description'),
                'cover'            => $coverName,
                'image'            => $imageName,
                'session_time'     => request()->input('session_time') ?? "",
                'price'            => request()->input('price_to_you') ?? 0,
                'private_session'  => request()->input('private_session'),
                'private'          => request()->input('is_private') ?? 0,
                'featured'         => request()->input('featured') ?? 0,
                'level'            => $getLevel[0],
                'price_to_you'     => request()->input('price_to_you') ?? 0,
                'price_to_student' => request()->input('price_to_student') ?? 0,
            ]);


            //course category
            if (!empty(request()->input('categories'))) {
                $CourseCategory = array();
                $categories     = json_decode(request()->input('categories'), true);
                foreach ($categories as $key => $value) {
                    $CourseCategory[] = new CourseCategory(['categories_id' => $value]);
                }
                $course->categories()->saveMany($CourseCategory);
            }

            //course languages
            if (!empty(request()->input('languages'))) {
                $CourseLanguage = array();
                $languages      = json_decode(request()->input('languages'), true);
                foreach ($languages as $key => $value) {
                    $CourseLanguage[] = new CourseLanguage(['language_id' => $value]);
                }
                $course->languages()->saveMany($CourseLanguage);
            }

            //course tags
            if (!empty(request()->input('tags'))) {
                $CourseTag = array();
                $tags      = json_decode(request()->input('tags'), true);
                foreach ($tags as $key => $value) {
                    $CourseTag[] = new CourseTag(['tag' => $value]);
                }
                $course->tags()->saveMany($CourseTag);
            }

            //course tags
            if (!empty(request()->input('level'))) {
                $course->levels()->delete();
                $CourseLevel    = array();
                $level          = request()->input('level');
                $getLevel       = explode(',', $level);
                for ($i=0; $i < count($getLevel); $i++) { 
                    $course_level = new CourseLevel;
                    $course_level->level_id = $getLevel[$i];
                    $course_level->course_id = $course->id;
                    $course_level->save();
                }
            }

            //FREQ=MONTHLY;BYDAY=FR;BYMONTHDAY=13;COUNT=5
            if (!empty(request()->input('available_dates'))) {
                $availabile_dates = json_decode(request()->input('available_dates'), true);
                
                foreach ($availabile_dates as $keyDates => $valueDates) {
                    $whenDate     = date("Y-m-d H:i:s", strtotime($valueDates['date']));
                    $hour = date("H", strtotime($whenDate));
                    $minute = date("i", strtotime($whenDate));
                    $minutes = ($hour*60)+$minute;
                    
                    $day   = date("d", strtotime($whenDate));
                    $month = date("m", strtotime($whenDate));
                    $year  = date("Y", strtotime($whenDate));

                    $availability = new CourseAvailability(['rule'=>'FREQ='.strtoupper($valueDates['repeat']),'when' => $whenDate,'day' => $day,'month' => $month,'year' => $year,'minutes' => $minutes, 'repeats' => $valueDates['repeat'] ?? "No Repeat", 'slots' => request()->input('slots')]);
                    $course->avail()->save($availability);
                }
            }
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }

        $data = $this->getCourseResponse($course);

        return $this->responseWithDataOREmpty('Successfully Created', 200, $data);
    }



    public function cancelSession()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'           => 'required', //
            'availability_id'   => 'required', //
            'all'               => 'required', //
            'end_date'          => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }
        //create Rrule format
        $endDate = date('Ymd', strtotime(request()->input('end_date'))).'T'.date('his', strtotime(request()->input('end_date')));
        $courseAvail       = CourseAvailability::find(request()->input('availability_id'));
       // dd($courseAvail->availabilityExclusion->exclusions); die;
        if(request()->input('all') == 0) {
            $SessionExclusion  = new SessionExclusion();
            $SessionExclusion->exclusions = $endDate;
            $SessionExclusion->availability_id = request()->input('availability_id');
            $SessionExclusion->save();
        }else{
            $courseAvail       = CourseAvailability::find(request()->input('availability_id'));
            $courseAvail->end_date   =  $endDate;
            $courseAvail->save();
        }
        
        return $this->responseWithDataOREmpty('Successfully updated', 200, $courseAvail);

    }

    //save/email paypal and xoom
    public function saveEmail()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'   => 'required', //
            'type'      => 'required', //
            'email'     => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }
        $users      = User::find(Auth::user()->id);
        if (request()->input('type') == 0) {
            $registerUserDetail = ['paypal' => request()->input('email')];
            foreach ($registerUserDetail as $key => $value) {
                $result = $users->updateMeta($key, $value);
            }
        } else {
            $registerUserDetail = ['xoom' => request()->input('email')];
            foreach ($registerUserDetail as $key => $value) {
                $result = $users->updateMeta($key, $value);
            }
        }

        $users      = User::find(Auth::user()->id);
        return $this->loginResponse('Successfully fetched', $users, true);
    }

    public function getEarnings()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'   => 'required', //
            'type'      => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        //get date for custom type
        $start       = request()->input('start') ?? "";
        $end         = request()->input('end') ?? "";

        $type       = request()->input('type');
        $getCarbon  = $this->getCarbon($type, $start, $end); //get carbon function date
        $book       = 0;
        $courseIDs  = array();
        $getAll     = Course::current()->get();

        if (!empty($getAll)) {
            foreach ($getAll as $key => $value) {
                $totalSum = $value->getTotalAmountAttribute($type, $getCarbon);
                if ($totalSum > 0) {
                    $courseIDs[] = $value->id;
                }
                $book += $totalSum;
            }
        }

        $getAllCourse     = Course::current()->whereIn('id', $courseIDs)->get();
        $availability = $this->getEarningsInstructor($getAllCourse);

        $data = array('total' => (string) $book, 'availability' => $availability);
        return $this->responseWithDataOREmpty('Successfully Fetched', 200, $data);
    }

    //edit course api
    public function EditCourse()
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

        try {
            if (request()->hasFile('cover')) {
                $cover                = request()->file('cover');
                $cover_name           = time() . '.' . $cover->getClientOriginalExtension();
                $destinationPathCover = base_path('/public/images/courses/');
                $imagePathCover       = $destinationPathCover . "/" . $cover_name;
                if (!$cover->move($destinationPathCover, $cover_name)) {
                    $this->responseWithError('Image uploaded failed. Please try again', 402);
                }
            }
            $coverName = '';
            if (isset($cover_name)) {
                $coverName = 'images/courses/' . $cover_name;
            }

            $Course = Course::find(request()->input('course_id'));
            if (!$Course) {
                $this->responseWithError('Social Feed not found', 402);
            }

            //remove old and assign new one
            if (request()->hasFile('cover')) {
                File::delete(base_path() . '/' . $Course->getAttributes()['cover']);
                $Course->cover = $coverName;
            }
            $Course->save();

            Course::findOrFail(request()->input('course_id'))->update(request()->except(['course_id', 'image', 'cover', 'categories', 'languages', 'tags', 'available_dates', 'repeat', 'slots', 'is_private', 'level']));

            Course::findOrFail(request()->input('course_id'))->update(['private' => request()->input('is_private') ?? 0]);

            $course = Course::findOrFail(request()->input('course_id'));

            if (!empty(request()->input('categories'))) {
                $course->categories()->delete();
                //course category
                $CourseCategory = array();
                $categories     = json_decode(request()->input('categories'), true);
                foreach ($categories as $key => $value) {
                    $CourseCategory[] = new CourseCategory(['categories_id' => $value]);
                }
                $course->categories()->saveMany($CourseCategory);
            }

            if (!empty(request()->input('languages'))) {
                $course->languages()->delete();
                //course language
                $CourseLanguage = array();
                $languages      = json_decode(request()->input('languages'), true);
                foreach ($languages as $key => $value) {
                    $CourseLanguage[] = new CourseLanguage(['language_id' => $value]);
                }
                $course->languages()->saveMany($CourseLanguage);
            }

            if (!empty(request()->input('tags'))) {
                $course->tags()->delete();
                //course tag
                $CourseTag = array();
                $tags      = json_decode(request()->input('tags'), true);
                foreach ($tags as $key => $value) {
                    $CourseTag[] = new CourseTag(['tag' => $value]);
                }
                $course->tags()->saveMany($CourseTag);
            }
            if (!empty(request()->input('level'))) {
                $course->levels()->delete();
                $CourseLevel    = array();
                $level          = request()->input('level');
                $getLevel       = explode(',', $level);
                for ($i=0; $i < count($getLevel); $i++) { 
                    $course_level = new CourseLevel;
                    $course_level->level_id = $getLevel[$i];
                    $course_level->course_id = $course->id;
                    $course_level->save();
                }
            }


            if (!empty(request()->input('available_dates'))) {
                $course->avail()->delete();
                //$availabile_dates = request()->input('available_dates');
                $availabile_dates = json_decode(request()->input('available_dates'), true);
                foreach ($availabile_dates as $keyDates => $valueDates) {
                    $whenDate     = date("Y-m-d H:i:s", strtotime($valueDates['date']));
                    
                    $hour = date("H", strtotime($whenDate));
                    $minute = date("i", strtotime($whenDate));
                    $minutes = ($hour*60)+$minute;
                    
                    $day   = date("d", strtotime($whenDate));
                    $month = date("m", strtotime($whenDate));
                    $year  = date("Y", strtotime($whenDate));


                    $availability = new CourseAvailability(['when' => $whenDate,'rule'=>'FREQ='.strtoupper($valueDates['repeat']),'day' => $day,'month' => $month,'year' => $year,'minutes' => $minutes, 'repeats' => $valueDates['repeat'], 'slots' => request()->input('slots')]);
                    $course->avail()->save($availability);
                }
            }
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }
        $data[] = $this->getCourseResponse($course);

        return $this->responseWithDataOREmpty('Successfully Updated', 200, $data);
    }

    //delete course
    public function DeleteCourse()
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

        $course = Course::findOrFail(request()->input('course_id'))->update(['deleted'=>1]);

        return $this->responseWithDataOREmpty('Successfully Deleted', 200, (object) array());
    }

    /*******************************Experience CRUD ***********************************/
    //save experience
    public function SaveExperience()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'    => 'required', //
            'company'    => 'required', //
            'title'      => 'required', //
            'summary'    => 'required', //
            'from_month' => 'required', //
            'to_month'   => 'required', //
            'from_year'  => 'required', //
            'to_year'    => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        try {
            //User::instructor()->findOrFail(Auth::user()->id);
            Experience::create(request()->all());
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }

        $getInstructor = $this->getInstructorData(); //getting instructor data

        return $this->responseWithDataOREmpty('Successfully Saved', 200, $getInstructor);
    }

    //update Experience
    public function UpdateExperience()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'       => 'required', //
            'experience_id' => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        try {
            //User::instructor()->findOrFail(Auth::user()->id);
            Experience::findOrFail(request()->input('experience_id'))->update(request()->except(['experience_id']));
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }

        $getInstructor = $this->getInstructorData(); //getting instructor data

        return $this->responseWithDataOREmpty('Successfully updated', 200, $getInstructor);
    }

    //delete experience
    public function DeleteExperience()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'       => 'required', //
            'experience_id' => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        try {
            //User::instructor()->findOrFail(Auth::user()->id);
            Experience::findOrFail(request()->input('experience_id'))->delete();
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }
        $getInstructor = $this->getInstructorData(); //getting instructor data

        return $this->responseWithDataOREmpty('Successfully deleted', 200, $getInstructor);
    }

    /*******************************Education CRUD ***********************************/
    public function SaveEducation()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'    => 'required', //
            'institute'  => 'required', //
            'title'      => 'required', //
            'from_month' => 'required', //
            'to_month'   => 'required', //
            'from_year'  => 'required', //
            'to_year'    => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        try {
            //User::instructor()->findOrFail(Auth::user()->id);
            Education::create(request()->all());
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }

        $getInstructor = $this->getInstructorData(); //getting instructor data

        return $this->responseWithDataOREmpty('We have saved your credentials successfully.', 200, $getInstructor);
    }

    public function saveStripe()
    {
        $validate = Validator::make(request()->all(), [
            'stripe_connect_id'   => 'required', //
            'stripe_connect_email'        => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        $user = User::find(Auth::user()->id);
        if (!$user) {
            $this->responseWithError(self::USERNFOUND, 402);
        }

        $updateProfile['stripe_connect_id'] = request()->input('stripe_connect_id');
        $updateProfile['stripe_connect_email']      = request()->input('stripe_connect_email');

        foreach ($updateProfile as $key => $value) {
            $result = $user->updateMeta($key, $value);
        }

        $users = User::find(Auth::user()->id);

        return $this->loginResponse('Successfully saved Stripe Details', $users, true);
    }

    //update Education
    public function UpdateEducation()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'      => 'required', //
            'education_id' => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        try {
            //User::instructor()->findOrFail(Auth::user()->id);
            Education::findOrFail(request()->input('education_id'))->update(request()->except(['education_id']));
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }

        $getInstructor = $this->getInstructorData(); //getting instructor data

        return $this->responseWithDataOREmpty('We have updated your credentials successfully.', 200, (object) array());
    }

    //delete education
    public function DeleteEducation()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'      => 'required', //
            'education_id' => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        try {
            //User::instructor()->findOrFail(Auth::user()->id);
            Education::findOrFail(request()->input('education_id'))->delete();
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }
       // $getInstructor = $this->getInstructorData(); //getting instructor data

        return $this->responseWithDataOREmpty('Successfully deleted', 200, null);
    }

    //leave ratings api
    public function leave_rating()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'       => 'required', //
            'course_id'     => 'required', //
            'instructor_id' => 'required', //
            'rating'        => 'required', //
            //'comments'      => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        try {
            //User::instructor()->findOrFail(Auth::user()->id);
            RatingsToInstructor::create(request()->all());
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }
       // $getInstructor = $this->getInstructorData(); //getting instructor data

        return $this->responseWithDataOREmpty('Thank you. We have taken your comments, and this will help us to serve better.', 200, null);
    }

    /*******************************Certificate CRUD ***********************************/
    public function SaveCertificate()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'   => 'required', //
            'institute' => 'required', //
            'title'     => 'required', //
            'year'      => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        try {
            //User::instructor()->findOrFail(Auth::user()->id);
            Certificate::create(request()->all());
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }

        $getInstructor = $this->getInstructorData(); //getting instructor data

        return $this->responseWithDataOREmpty('Successfully Saved', 200, $getInstructor);
    }

    //update Certificate
    public function UpdateCertificate()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'        => 'required', //
            'certificate_id' => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        try {
            //User::instructor()->findOrFail(Auth::user()->id);
            Certificate::findOrFail(request()->input('certificate_id'))->update(request()->except(['certificate_id']));
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }

        $getInstructor = $this->getInstructorData(); //getting instructor data

        return $this->responseWithDataOREmpty('Successfully updated', 200, $getInstructor);
    }

    //delete Certificate
    public function DeleteCertificate()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'        => 'required', //
            'certificate_id' => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        try {
            //User::instructor()->findOrFail(Auth::user()->id);
            Certificate::findOrFail(request()->input('certificate_id'))->delete();
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }
        $getInstructor = $this->getInstructorData(); //getting instructor data

        return $this->responseWithDataOREmpty('Successfully deleted', 200, $getInstructor);
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

        $user_id = Auth::user()->id;
        $month  = request()->input('month') ?? "";
        $day    = request()->input('date')  ?? "";
        $year   = request()->input('year')  ?? "";
        $Courses = Course::where('user_id', $user_id)->where('deleted',0)->get();
        $availability = array();
        foreach ($Courses as $course) {
            $availability[] = $this->getInstructorCalendar($course, $month, $day, $year);
        }
        $oneDimensionalArray = call_user_func_array('array_merge', $availability);
        $data = [];
        foreach($oneDimensionalArray as $filterArray){
            if(empty($filterArray['course']['availability']))
            continue;
            $data[] = $filterArray;
        }
        return $this->responseWithDataOREmpty('Successfully fetched', 200, $data);
    }

    //calendar group view
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
            $Courses = Course::where('user_id', $user_id)->get();
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }

        $availability = array();
        foreach ($Courses as $course) {
            // code...
            $availability = $this->getInstructorGroupCalendar($course);
        }
        return $this->responseWithDataOREmpty('Successfully fetched', 200, $availability);
    }


    //Script for update All availability rule 

    public function updateAvailabilityScript(){

        $CourseAvailability  = CourseAvailability::where('rule',null)->get();
        foreach($CourseAvailability as $availability){

            CourseAvailability::where('id',$availability->id)
            ->update(['rule'=> 'FREQ='.strtoupper($availability->repeats)]);
        }
        return $CourseAvailability;

    }

    //update_appointment

    public function updateAppointment(){
        $validate = Validator::make(request()->all(), [
            'user_id'   =>  'required', //
            'booking_id'=>  'required',
            'status'    =>  'required'
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        } 
        Booking::where('id',request()->input('booking_id'))->update(['status'=>request()->input('status')]);
        return response()->json(['message'=>'Appointment has been updated successfully.','status'=>200,'success'=>true], 200); 
    }
    //request_commission

    public function requestCommission(){
        $validate = Validator::make(request()->all(), [
            'user_id'   =>  'required'
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        } 
        $data = Setting::where('name','Commission')->first();
        $value = 10;
        if(!empty($data)){
            $value = $data->value;
        }
        
        return response()->json(['message'=>'Successfully fetched','status'=>200,'success'=>true,'data'=>['commission'=>$value]],200); 
    }

    //register_push

    public function registerPush(){
        $validate = Validator::make(request()->all(), [
            'user_id'   =>  'required',
            'token'     =>  'required'
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        } 

        $pushToken = PushToken::where('token',request()->input('token'))->first();

        if(empty($pushToken)){
            $pushToken  = new PushToken();
            $pushToken->user_id  =  request()->input('user_id');
            $pushToken->token    =  request()->input('token');
            $pushToken->save();
        }else{
            $pushToken->user_id  = request()->input('user_id');
            $pushToken->save();
        }
        return response()->json(['message'=>'Data has been inserted successfully.','status'=>200,'success'=>true], 200);
    }

    //send_push

    public function sendPush(){
        $validate = Validator::make(request()->all(), [
            'user_id'   =>  'required',
            'to_user_id'=>  'required',
            'message'   =>  'required',
            'session_id'=>  'required'
        ]);
        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        } 
        $firebaseToken  =   PushToken::where('user_id',request()->input('to_user_id'))
                            ->pluck('token')->toArray(); 
        $Itoken         = array_values($firebaseToken);
        $data = [
            "registration_ids"  => $Itoken,
            "content_available" => true,
            "mutable_content"   => true,
            "priority"          => "high",
            "notification"      => [
                "title" => 'Title',
                "body"  => request()->input('message'),
                "sound" => "default",
            ],
            "data"              => [
                "session_id" => request()->input('session_id') ?? "",
            ],
        ];
        $this->sendPushCrul($data);
        return response()->json(['message'=>'Notification has been sent successfully.','status'=>200,'success'=>true], 200);
    }

    //insturctor/ cancel_session
    public function insturctorCancelSession(){
        $validate = Validator::make(request()->all(), [
            'user_id'           =>  'required',
            'session_id'        =>  'required',
            'availability_id'   =>  'required',
            'date_utc'          =>  'required'
        ]);
        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }
        //change date format for When package
        //$date_utc = date('Ymd', strtotime(request()->input('date_utc'))).'T'.date('his', strtotime(request()->input('date_utc')));
        $SessionExclusion  = new SessionExclusion();
        $SessionExclusion->exclusions = request()->input('date_utc');
        $SessionExclusion->availability_id = request()->input('availability_id');
        $SessionExclusion->save();

        Booking::where(['when'=>request()->input('date_utc'),'availability_id'=>request()->input('availability_id')])->update(['status'=>'4']);

        $students = Booking::where(['when'=>request()->input('date_utc'),'availability_id'=>request()->input('availability_id')])->pluck('user_id')->toArray();
        $user =User::find(request()->input('user_id'));
        $session        = Course::find(request()->input('session_id'));
        if($session == null){
            return $this->responseWithError('Session_id is invalid', 401);
        }
        $msg = $user->first_name.' '.$user->last_name.' has cancelled session for '.$session->title.' scheduled on date '.date('Y-m-d',strtotime(request()->input('date_utc'))).' at '.date('H:i',strtotime(request()->input('date_utc')));
        $this->saveNotifications($user,$students,request()->input('session_id'),request()->input('availability_id'),$msg,request()->input('date_utc'));
        $firebaseToken  =   PushToken::whereIn('user_id',$students)
                            ->pluck('token')->toArray(); 
        $Itoken         = array_values($firebaseToken);
        $data = [
            "registration_ids"  => $Itoken,
            "content_available" => true,
            "mutable_content"   => true,
            "priority"          => "high",
            "notification"      => [
                "title"         => 'Session Cancelled',
                "body"          => $msg,
                "sound" => "default",
            ],
            "data"              => [
                "availability_id" => request()->input('availability_id') ?? "",
            ],
        ];
        $this->sendPushCrul($data);

        return response()->json(['message'=>'Session has been cancel successfully.','status'=>200,'success'=>true], 200);
    }

    //enrolled_list

    public function enrolledList(){
        $validate = Validator::make(request()->all(), [
            'user_id'           =>  'required',
            'availability_id'   =>  'required',
            'when'          =>  'required'
        ]);
        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }
        
        $user_ids    = Booking::where(['when'=>request()->input('when'),
                            'availability_id'=>request()->input('availability_id')
                                ])->pluck('user_id');
        $users       = User::whereIn('id',$user_ids)->get();
        return $this->responseWithDataOREmpty('Successfully Updated', 200, $users);

    }
    // get session
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
        $userCourse    = Course::where('user_id',request()->input('user_id'))
                        ->whereHas('avail', function ($q) {
                                $q->where('when','>',request()->input('date'));
                            })->with('avail')->get();
        
        $data = [];

        foreach($userCourse as $course){
            foreach($course->avail as $avail){
                $avalibiltyObj =  new  \stdClass();
                $avalibiltyObj->id          =   $avail->id;
                $avalibiltyObj->when_utc        =   $avail->when;
                $avalibiltyObj->repeats     =   $avail->repeats;
                $avalibiltyObj->rule        =   $avail->rule;
                $avalibiltyObj->course_id   =   $course->id;
                $avalibiltyObj->title       =   $course->title;
                $avalibiltyObj->description =   $course->description;
                $avalibiltyObj->session_time=   $course->session_time;

                $data[]  = $avalibiltyObj;
            }
        } 
        return $this->responseWithDataOREmpty('Successfully Updated', 200, $data);
        //return  $data; 
    }


    public function sendStartSessionNotification(){
        $validate = Validator::make(request()->all(), [
            'user_id'           =>  'required',
            'course_id'         =>  'required',
            'availability_id'   =>  'required',
            'when'              =>  'required'
        ]);
        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }
        $when       = date('Y-m-d H:i:s',request()->input('when'));
        $user       =   User::find(request()->input('user_id'));
        $students    =   Booking::where(['when'=>$when,
                            'availability_id'=>request()->input('availability_id'),'status'=>1
                                ])->pluck('user_id');
        $course = Course::find(request()->input('course_id'));
        $msg    = $user->first_name.' '.$user->last_name.' has started a session for '.$course->title.' scheduled on date '.date('Y-m-d',request()->input('when')).' at '.date('H:i',request()->input('when')).'. Please join the session.';
        $this->saveNotifications($user,$students,request()->input('course_id'),request()->input('availability_id'),$msg,$when);
        $firebaseToken  =   PushToken::whereIn('user_id',$students)
                            ->pluck('token')->toArray(); 
        $Itoken         = array_values($firebaseToken);
        $data = [
            "registration_ids"  => $Itoken,
            "content_available" => true,
            "mutable_content"   => true,
            "priority"          => "high",
            "notification"      => [
                "title"         => 'Session Started',
                "body"          => $msg,
                "sound" => "default",
            ],
            "data"              => [
                "availability_id" => request()->input('availability_id') ?? "",
            ],
        ];
        $this->sendPushCrul($data); 
        return response()->json(['message'=>'Notification has been sent successfully.','status'=>200,'success'=>true], 200);              
    }

    public function saveNotifications($sender,$receivers,$course_id,$availability_id,$msg,$when){
        $data  = [];
        foreach($receivers as $key => $receiver){
            $data[] = [
                 'user_id' => $receiver,
                 'when' => $when,
                 'availability_id' => $availability_id,
                 'other_user_id' => $sender->id,
                 'title' => $msg,
                 'name' => $sender->first_name.' '.$sender->last_name,
                 'course_id' => $course_id,
                 'image' => $sender->profile_photo_path,
                 'read' => 0,
                 'created_at'=>Carbon::now(),
                 'updated_at'=>Carbon::now(),

            ];
        }
        Notification::insert($data);
    }

    public function myCourseAvailability(){
        $validate = Validator::make(request()->all(), [
            'user_id'       => 'required', //
            'course_id'     => 'required', //
            'from_utc'         => 'required',
            'to_utc'          => 'required'
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }
        $from_utc = request()->input('from_utc');
        $to_utc  = request()->input('to_utc');

        $data = [];
        $course = Course::find(request()->input('course_id'));
        if(!empty($course->Avail)){
            $availability = $this->getAvailabilityUnix($course->avail, $from_utc, $to_utc);
            $data = [];
            $availId = [];
            foreach($availability as $avail){
                if (!in_array($avail->id, $availId))
                {
                  $availId[] = $avail->id;
                  $data[] = $avail;
                }
            }
        }

        return $this->responseWithDataOREmpty('Successfully Fetched.', 200, $data);        
    }

}
