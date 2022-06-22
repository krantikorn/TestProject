<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ResetMail;
use App\Models\Category;
use App\Models\Course;
use App\Models\PaymentMethod;
use App\Models\Booking;
use App\Models\User;
use App\Models\UserCategory;
use App\Models\Notification;
use App\Models\Card;
use App\Traits\Meta;
use App\Models\PaymentPreference;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;

class CommonController extends Controller
{
    use Meta;
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['categories', 'ForgetPassword', 'GetPassword', 'UpdatePassword']]);
        $this->checkConnection();
    }

    /************ get all categories**********************/
    public function categories()
    {
        $message = 'All Categories';
        $data    = Category::get();
        if (empty($data)) {
            $message = 'No Categories';
        }
        return $this->responseWithDataOREmpty($message, 200, $data);
    }

    /************ save user categories**********************/
    public function SaveCategories()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'   => 'required',
            'interests' => 'required',
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
            $interests = request()->input('interests');
            foreach ($interests as $key => $value) {
                //save User category
                UserCategory::updateOrCreate(
                    ['user_id' => Auth::user()->id, 'category_id' => $value],
                    [
                        'user_id'     => Auth::user()->id,
                        'category_id' => $value,
                    ]
                );
            }
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }

        $message = 'All Categories';
        $data    = UserCategory::where('user_id', Auth::user()->id)->get();
        if (empty($data)) {
            $message = 'No Categories';
        }
        return $this->responseWithDataOREmpty($message, 200, $data);
    }

    //forget password
    public function ForgetPassword()
    {
        $validate = Validator::make(request()->all(), [
            'email' => 'required|email|exists:users',
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                if ($message == 'The selected email is invalid.') {
                    $validateMessage .= 'Email doesn\'t exist. ';
                } else {
                    $validateMessage .= $message;
                }
            }
            return $this->responseWithError($validateMessage, 402);
        }

        $users = User::where('email', request()->input('email'))->first();
        if (!$users) {
            return $this->responseWithError(self::USERNFOUND, 402);
        }

        $registerUserDetail = ['forgetPassword' => 0];
        foreach ($registerUserDetail as $key => $value) {
            $result = $users->updateMeta($key, $value);
        }

        try {
            Mail::to($users->email)->send(new ResetMail($users));
            if (Mail::failures()) {
                return $this->responseWithError(self::ERMAIL, 402);
            }
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }

        return $this->respondWithMessage(self::SMAIL, true, 200);
    }

    //search api
    public function Search()
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

        $keyword = request()->input('keyword') ?? ""; //get keyword

        $users           = User::find(Auth::user()->id);
        $categories      = $users->categories;
        $groupCategories = [];
        foreach ($categories as $key => $value) {
            /*if (!empty($keyword)) {
                if (strpos($value->name, $keyword) !== false) {
                    $groupCategories[] = $this->getSavedCategories($value);
                }
            } else {*/
                $groupCategory = $this->getSavedCategories($value);
                $groupCategories[] = $value->category_id;
            //}
        }
        /*************** interest array ************************/
        $courses    = Course::getPublicSession();
        $allCourses = $this->filters($courses);
        
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
                $CourseDetails = $this->getCourseResponse($value,'',true,true);
                if (empty($CourseDetails['availability']))
                continue;
                $check[] = $CourseDetails; //$this->getCourseResponse($value,'',true,true);
            }
        }

        /*******************************************************************/

        //#3
        $course     = Course::getPublicSession();
        $courses    = $this->filters($course);

        //$courses = $course->where('featured', 1)->get();
        //get all featured courses
        $getAllFeaturedCourses = array();
        $getAllSearchedCoursesIntructors =[];
        foreach ($courses as $key => $value) {
            $CourseDetails = $this->getCourseResponse($value,'',true,true);
            if (empty($CourseDetails['availability']))
            continue;
            $getAllSearchedCoursesIntructors[]  = $value->user_id;
            $getAllFeaturedCourses[] = $CourseDetails;
        }

        //#4
        $getAllUser = User::query();
        if (!empty($keyword)) {
            $getAllUser->where(function ($query) use ($keyword) {
                $query->where('first_name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('last_name', 'LIKE', '%' . $keyword . '%');
            });
        }
        $getAllUsers = $getAllUser->where('account_type', 1)->orWhereIn('id',$getAllSearchedCoursesIntructors)->get();
        
        $intructors  = array();
        foreach ($getAllUsers as $key => $users) {
            $featured = $this->getMetaValue($users, 'featured'); //fcm_token
            //if ($featured == 1) {
                $intructors[] = $this->getInstructor($users->id);
            //}
        }
        $data = array('interests' => $check, 'courses' => $getAllFeaturedCourses, 'instructors' => $intructors);
        return $this->responseWithDataOREmpty('Successfully Fetched', 200, $data);
    }

    //change password
    public function ChangePassword()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'          => 'required',
            'old_password'     => 'required',
            'new_password'     => 'required|max:10|same:new_password',
            'confirm_password' => 'required|max:10|same:new_password',
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 200);
        }

        $user_id      = request()->input('user_id');
        $old_password = request()->input('old_password');

        $new_password   = request()->input('new_password');
        $setNewPassword = Hash::make($new_password);
        $set            = ['password' => $setNewPassword];
        $user           = User::find(Auth::user()->id);

        $current_password = Auth::user()->password;
        if (!Hash::check($old_password, $current_password)) {
            return $this->responseWithError(self::OLDPASSWORD, 200);
        }

        if (Hash::check($new_password, $current_password)) {
            return $this->responseWithError(self::INCORRECT, 200);
        }

        $user = User::where('id', Auth::user()->id)->update($set);
        if (!$user) {
            return $this->responseWithError(self::USERNFOUND, 200);
        }

        return $this->respondWithMessage(self::SUCCESSUPDATED, true, 200);
    }

    public function getProfile()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'    => 'required',
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
        //$extradata = array('code' => $code, 'tag_line' => $tag_line ?? '');
        //return $this->respondWithData($userUpdate, 200, $extradata);
        return $this->loginResponse('Successfully fetched', $users, true);
        //return $this->responseWithoutToken('Successfully fetched', $users, 200);
    }

    //update profile
    public function UpdateProfile()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'    => 'required',
            'first_name' => 'required',
            'last_name'  => 'required',
            'image'      => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'phone'      => 'required',
            'about'      => 'required',
            'tag_line'   => 'required',
            //'phone'      => 'required',
            //'code'       => 'required',
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                if ($message == 'The image failed to upload.') {
                    $validateMessage .= 'Image should not be greater than 2Mb';
                } else {
                    $validateMessage .= $message;
                }
            }
            return $this->responseWithError($validateMessage, 402);
        }

        $user_id    = request()->input('user_id');
        $first_name = request()->input('first_name');
        $last_name  = request()->input('last_name');
        $phone      = request()->input('phone') ?? '';
        $about      = request()->input('about') ?? '';
        $tag_line   = request()->input('tag_line') ?? '';

        if (request()->hasFile('image')) {
            $image           = request()->file('image');
            $image_name      = time() . '.' . $image->getClientOriginalExtension();
            $destinationPath = base_path('/public/images/profile/');
            $imagePath       = $destinationPath . "/" . $image_name;
            /*$image->move($destinationPath, $image_name);*/
            if (!$image->move($destinationPath, $image_name)) {
                $this->responseWithError('Image uploaded failed. Please try again', 402);
            }
        }
        $imageName = '';
        if (isset($image_name)) {
            $imageName = 'images/profile/' . $image_name;
        }

        $user = User::find(Auth::user()->id);
        if (!$user) {
            $this->responseWithError(self::USERNFOUND, 402);
        }

        //check for image
        if (request()->hasFile('image')) {
            $this->unlink_files($user); //deleting old file
            $updateProfile['image'] = $imageName;
        }

        //check for lat and long
        /*if (!empty(request()->input('lat'))) {
        $updateProfile['lat'] = request()->input('lat');
        }

        $long = request()->input('long') ?? request()->input('lng');
        if (!empty($long)) {
        $updateProfile['long'] = $long;
        }*/

        if (!empty($about)) {
            $updateProfile['about'] = request()->input('about');
        }

        if (!empty($tag_line)) {
            $updateProfile['tag_line'] = request()->input('tag_line');
        }

        if (!empty($updateProfile)) {
            foreach ($updateProfile as $key => $value) {
                $result = $user->updateMeta($key, $value);
            }
            if (!$result) {
                return $this->responseWithError(self::UPDATEERROR, 402);
            }
        }

        if (!empty(request()->input('phone'))) {
            if (request()->input('phone') != $user->phone) {
                /***********phone validation starts**************************/
                $validate = Validator::make(request()->all(), [
                    'phone' => 'required|unique:users',
                ]);
                if ($validate->fails()) {
                    $errors = $validate->errors();
                    return $this->responseWithError($errors->first('phone'), 402);
                }
                /***********phone validation ends**************************/
            }
            $user->phone = request()->input('phone');
        }
        $user->first_name = $first_name;
        $user->last_name  = $last_name;
        try {
            $user->save();
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }

        $userUpdate = User::find($user->id);
        $code       = $this->getMetaValue($userUpdate, 'about'); //get code from user details table
        $tag_line   = $this->getMetaValue($userUpdate, 'tag_line'); //get address from user details table
        //$lat        = $this->getMetaValue($userUpdate, 'lat'); //get lat from user details table
        //$long       = $this->getMetaValue($userUpdate, 'long'); //get long from user details table
        $extradata = array('code' => $code, 'tag_line' => $tag_line ?? '');
        //return $this->respondWithData($userUpdate, 200, $extradata);
        return $this->loginResponse('We have updated your profile successfully.', $userUpdate, true);
        //return $this->responseWithoutToken('Successfully updated', $userUpdate, 200);
    }

    //save Payment method
    public function SavePaymentMethod()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'      => 'required', //
            'payment_type' => 'required', //
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
            PaymentMethod::create(request()->all());
        } catch (\Throwable $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }
        $PaymentMethod = PaymentMethod::get();
        $data          = $this->getPaymentMethodDetails($PaymentMethod);

        return $this->responseWithDataOREmpty('Successfully Saved', 200, $data);
    }

    //get Payment method
    public function GetPaymentMethod()
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

        $PaymentMethod = PaymentMethod::get();
        $data          = $this->getPaymentMethodDetails($PaymentMethod);

        return $this->responseWithDataOREmpty('Successfully Saved', 200, $data);
    }

    //update Payment method
    public function UpdatePaymentMethod()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'           => 'required', //
            'payment_method_id' => 'required', //
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
            $PaymentMethod = PaymentMethod::find(request()->input('payment_method_id'))->update(request()->except(['payment_method_id']));
        } catch (\Throwable $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }
        $PaymentMethod = PaymentMethod::get();
        $data          = $this->getPaymentMethodDetails($PaymentMethod);

        return $this->responseWithDataOREmpty('Successfully Updated', 200, $data);
    }

    //delete payment method
    public function DeletePaymentMethod($value = '')
    {
        $validate = Validator::make(request()->all(), [
            'user_id'           => 'required', //
            'payment_method_id' => 'required', //
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
            $PaymentMethod = PaymentMethod::find(request()->input('payment_method_id'))->delete();
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }
        $PaymentMethod = PaymentMethod::get();
        $data          = $this->getPaymentMethodDetails($PaymentMethod);

        return $this->responseWithDataOREmpty('Successfully Deleted', 200, $data);
    }


    //slotsAvailable
    public function slotsAvailable()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'    => 'required', //
            'course_id'  => 'required', //
            'when'       => 'required', //
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

            $bookings = Booking::query()
                            ->where([['course_id', '=', request()->input('course_id')], ['when', '=', request()->input('when')]])
                            ->with('availability:id,slots')
                            ->get();

            $slots = 0;
            foreach ($bookings as $key => $value) {
               $slots = $value->availability->slots;
            }

            $pending_slots  = $slots - count($bookings);
            $data           = array('slots' => (string) $slots, 'pending_slots' => (string) $pending_slots );

            return $this->responseWithDataOREmpty('Successfully fetched', 200, $data);

        } catch (\Throwable $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }
    }

    /************************************ Card Module **********************************/
    //get Card method
    public function cards()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'       => 'required', //
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        $card          = Card::get();
        $data          = $this->getCardDetails($card);

        return $this->responseWithDataOREmpty('Successfully fetched', 200, $data);
    }

    //save Card method
    public function saveCard()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'       => 'required', //
            'last'          => 'required', //
            'type'          => 'required', //
            'name'          => 'required', //
            'primary'       => 'required', //
            'customer_id'   => 'required', //
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
            if(request()->input('primary') == 1) {
                Card::query()->update(['primary' => 0]);
            }
            Card::create(request()->all());
        } catch (\Throwable $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }
        $card          = Card::get();
        $data          = $this->getCardDetails($card);

        return $this->responseWithDataOREmpty('Successfully Saved', 200, $data);
    }

    //update Card method
    public function updateCard()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'       => 'required', //
            'card_id'       => 'required', //
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
            if(request()->input('primary') == 1) {
                Card::query()->update(['primary' => 0]);
            }
            Card::find(request()->input('card_id'))->update(request()->except(['card_id']));
        } catch (\Throwable $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }
        $card = Card::get();
        $data = $this->getCardDetails($card);

        return $this->responseWithDataOREmpty('Successfully Updated', 200, $data);
    }


    //delete Card method
    public function deleteCard()
    {
        $validate = Validator::make(request()->all(), [
            'user_id'       => 'required', //
            'card_id'       => 'required', //
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
            $card = Card::find(request()->input('card_id'))->delete();
        } catch (\Throwable $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }
        $card = Card::get();
        $data = $this->getCardDetails($card);

        return $this->responseWithDataOREmpty('Successfully Deleted', 200, $data);
    }

  /*  public function courseAvailability(){ // gives data acc to month and year
        $validate = Validator::make(request()->all(), [
            'user_id'       => 'required', //
            'course_id'     => 'required', //
            'month'         => 'required',
            'year'          => 'required'
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }
        $month = request()->input('month');
        $day   = '';
        $year  = request()->input('year');
        $data = [];
        $course = Course::find(request()->input('course_id'));
        if(!empty($course->Avail)){
            $data = $this->getAvailabilityNew($course->avail, $month, $day, $year);
        }

        return $this->responseWithDataOREmpty('Successfully Fetched.', 200, $data);        
        
    } */

    public function courseAvailability(){
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
            $data = $this->getAvailabilityUnix($course->avail, $from_utc, $to_utc);
        }

        return $this->responseWithDataOREmpty('Successfully Fetched.', 200, $data);        
        
    }

    public function notifications(){
        $validate = Validator::make(request()->all(), [
            'user_id'           => 'required', //
            'account_type'     => 'required'
        ]);
        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }  

        $data = Notification::select('*', 'when as when_utc')->where('user_id',request()->input('user_id'))->get();
        return $this->responseWithDataOREmpty('Successfully Fetched.', 200, $data);
    }

    public function paymentPreferences(Request $request){
        $validate = Validator::make(request()->all(), [
            'user_id'           => 'required', //
            'type'              => 'required', //
        ]);
        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }   
        $is_payment_preference = PaymentPreference::where('user_id',$request->user_id)->first();
        if(empty($is_payment_preference)){
            $payment_preference  = new PaymentPreference;
            $payment_preference->type               = $request->type;
            $payment_preference->user_id            = $request->user_id;
            $payment_preference->email              = $request->email??null;
            $payment_preference->stripe_connect_id  = $request->stripe_connect_id??null;
            $payment_preference->save();
            return $this->responseWithDataOREmpty('Successfully Created.', 200, $payment_preference);
        }else{
            $is_payment_preference->type                = $request->type;
            $is_payment_preference->user_id             = $request->user_id;
            $is_payment_preference->email               = $request->email??null;
            $is_payment_preference->stripe_connect_id   = $request->stripe_connect_id??null;
            $is_payment_preference->save();
            return $this->responseWithDataOREmpty('Successfully Updeted.', 200, $is_payment_preference);
        }
    }
    
    public function getPaymentPreferences(Request $request){

        $validate = Validator::make(request()->all(), [
            'user_id'           => 'required'
        ]);
        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }  
        $is_payment_preference = PaymentPreference::where('user_id',$request->user_id)->first();
        if(empty($is_payment_preference)){
            $data = new  \stdClass();
            $data->type                = -1;
            $data->user_id             = $request->user_id;
            $data->email               = null;
            $data->stripe_connect_id   = null;
            return $this->responseWithDataOREmpty('Successfully Fetched.', 200, $data);
        }

        return $this->responseWithDataOREmpty('Successfully Fetched.', 200, $is_payment_preference);
    }

    public function request100msToken(){
        $app_access_key = "628fbb3fb873787aa26f6c86";
        $app_secret = "WXZLBg3OWieYf3JRGq6QC4taVFnGIlSyuRczAiwxTHAIUmrM9tbfs8_rxHfNm0cuZSa6JaQbd_2yYZWlDNxFY9hFID6gU_aaT2Paju83V_-IDsPdQ2Bxd-8nlqN2AJEHiFjxQuykFInNYwVGHd7HYiB3-YPPiS_Y3pyXwsfHzpM=";
        
        $issuedAt   = Carbon::now();
        $expire     = Carbon::now()->addDay();
        $payload = [
            'access_key' => $app_access_key,
            'type' => 'management',
            'version' => 2,
            'jti' =>  Uuid::uuid4()->toString(),
            'iat'  => $issuedAt->getTimestamp(),
            'nbf'  => $issuedAt->getTimestamp(),
            'exp'  => $expire->getTimestamp(),
        ];
        
        $token['token'] = JWT::encode($payload, $app_secret, 'HS256');
        return $this->responseWithDataOREmpty('Successfully Fetched.', 200, $token);
    }
}
