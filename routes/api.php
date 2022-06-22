<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CommonController;
use App\Http\Controllers\Api\InstructorController;
use App\Http\Controllers\Api\StudentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
return $request->user();
});
 */
Route::group([
    'middleware' => 'api',
    //'prefix'     => 'auth',

], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/setAccountType', [AuthController::class, 'setAccountType']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::post('/categories', [CommonController::class, 'categories']);
    Route::post('/save_categories', [CommonController::class, 'SaveCategories']);
    Route::post('/forget_password', [CommonController::class, 'ForgetPassword']); // Matches "/api/forget_password
    Route::post('change_password', [CommonController::class, 'ChangePassword']); // Matches "/api/change_password
    Route::post('update_profile', [CommonController::class, 'UpdateProfile']); // Matches "/api/update_profile
    Route::post('profile', [CommonController::class, 'getProfile']); // Matches "/api/get_profile
    Route::post('dashboard', [StudentController::class, 'dashboard']); // Matches "/api/dashboard
    Route::post('instructor/dashboard', [InstructorController::class, 'dashboard']); // Matches "/api/dashboard
    Route::post('earnings', [InstructorController::class, 'getEarnings']); //earnings

    Route::post('search', [CommonController::class, 'Search']); // Matches "/api/search
    Route::get('/payment_method', [CommonController::class, 'GetPaymentMethod']);
    Route::post('/payment_method', [CommonController::class, 'SavePaymentMethod']);
    Route::put('/payment_method', [CommonController::class, 'UpdatePaymentMethod']);
    Route::delete('/payment_method', [CommonController::class, 'DeletePaymentMethod']);

    Route::post('/slots_available', [CommonController::class, 'slotsAvailable']);
    Route::post('/cancel_session', [InstructorController::class, 'cancelSession']);

    //Card routes
    Route::get('/card', [CommonController::class, 'cards']);
    Route::post('/card', [CommonController::class, 'saveCard']);
    Route::put('/card', [CommonController::class, 'updateCard']);
    Route::delete('/card', [CommonController::class, 'deleteCard']);

    Route::post('/instructors', [StudentController::class, 'Instructors']); // Matches "/api/instructors
    Route::post('courses', [StudentController::class, 'Courses']); // Matches "/api/courses
    Route::post('filter', [StudentController::class, 'FilterCourses']); // Matches "/api/courses
    Route::post('/course_details', [StudentController::class, 'CourseDetails']);
    Route::post('/mark_instructor_favorite', [StudentController::class, 'MarkInstructor']);
    Route::post('/mark_course_favorite', [StudentController::class, 'MarkCourse']);
    Route::post('/my_favorite', [StudentController::class, 'myFavorite']);

    Route::post('/enroll', [StudentController::class, 'CreateBooking']);
    Route::post('/booking_history', [StudentController::class, 'bookingHistory']);

    Route::post('/leave_rating', [InstructorController::class, 'leave_rating']);
    Route::post('/stripe/save', [InstructorController::class, 'saveStripe']);
    Route::post('/save/email', [InstructorController::class, 'saveEmail']); // Matches "/api/save/email

    Route::post('/save/payment/status', [InstructorController::class, 'savePaymentStatus']); // Matches "/api/save/email

    //instructor Controller
    Route::post('/instructor_details', [InstructorController::class, 'InstructorDetails']); // Matches "/api/instructor_details
    Route::post('/getCourseMaterial', [InstructorController::class, 'GetCourseMaterial']);
    Route::post('/create_course', [InstructorController::class, 'CreateCourse']);
    Route::post('/edit_course', [InstructorController::class, 'EditCourse']);
    Route::delete('/delete_course', [InstructorController::class, 'DeleteCourse']);

    //Experience routes
    Route::post('/experience', [InstructorController::class, 'SaveExperience']);
    Route::put('/experience', [InstructorController::class, 'UpdateExperience']);
    Route::delete('/experience', [InstructorController::class, 'DeleteExperience']);

    //Education routes
    Route::post('/education', [InstructorController::class, 'SaveEducation']);
    Route::put('/education', [InstructorController::class, 'UpdateEducation']);
    Route::delete('/education', [InstructorController::class, 'DeleteEducation']);

    //Certificate routes
    Route::post('/certificate', [InstructorController::class, 'SaveCertificate']);
    Route::put('/certificate', [InstructorController::class, 'UpdateCertificate']);
    Route::delete('/certificate', [InstructorController::class, 'DeleteCertificate']);

    //calendar view
    Route::post('/calendar', [StudentController::class, 'CalendarView']);
    Route::post('/calendar/group', [StudentController::class, 'CalendarGroupView']);
    //calendar view
    Route::post('/insturctor/calendar', [InstructorController::class, 'CalendarView']);
    Route::post('/insturctor/calendar/group', [InstructorController::class, 'CalendarGroupView']);

    //update Availability Script 
    Route::get('/updateAvailabilityScript', [InstructorController::class, 'updateAvailabilityScript']);
    //new route
    Route::post('/request_cancel', [StudentController::class, 'requestCancel']);
    Route::post('/insturctor/update_appointment', [InstructorController::class, 'updateAppointment']);
    Route::post('/request_commission', [InstructorController::class, 'requestCommission']);
    Route::post('/register_push', [InstructorController::class, 'registerPush']);
    Route::post('/send_push', [InstructorController::class, 'sendPush']);
    Route::post('insturctor/cancel_session', [InstructorController::class, 'insturctorCancelSession']);
    Route::post('insturctor/enrolled_list', [InstructorController::class, 'enrolledList']);
    Route::get('insturctor/get_session', [InstructorController::class, 'getSession']);
    Route::get('/get_session', [StudentController::class, 'getSession']);
    Route::post('course_availability', [CommonController::class, 'courseAvailability']); 
    Route::post('insturctor/send_start_session_notification', [InstructorController::class, 'sendStartSessionNotification']); 
    Route::post('notifications', [CommonController::class, 'notifications']); 
    Route::post('insturctor/my_course_availability', [InstructorController::class, 'myCourseAvailability']); 
    //
    Route::post('payment_preferences',[CommonController::class, 'paymentPreferences']); 
    Route::get('payment_preferences', [CommonController::class, 'getPaymentPreferences']); 
    Route::get('request100msToken', [CommonController::class, 'request100msToken']); 

});
