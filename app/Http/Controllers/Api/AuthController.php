<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSetting;
use App\Models\User;
use App\Traits\Meta;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    use Meta;

    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->checkConnection();
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        if(!isset($request->login_type ) || $request->login_type == '0'){
            $validate = Validator::make(request()->all(), [
                'email'    => 'required|email|exists:users',
                'password' => 'required',
            ]);

            $credentials = $request->only('email', 'password');
 
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

            if (Auth::attempt($credentials)) {
                $user = Auth::user();
                if (empty($user)) {
                    return $this->responseWithError(self::USERNFOUND, 402);
                }
                $fcm_token = ['fcm_token' => request()->input('fcm_token')];
                foreach ($fcm_token as $key => $value) {
                    $result = $user->updateMeta($key, $value);
                }
                return $this->loginResponse('Successfully Login', $user, true);
            }
            return $this->responseWithError(self::INPASS, 402);
        }else{ 
            $validate = Validator::make(request()->all(), [
                'social_id'    => 'required',
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

            $user = User::where('social_id',$request->social_id)->first();
            if($user){
                Auth::login($user);
                $fcm_token = ['fcm_token' => request()->input('fcm_token')];
                foreach ($fcm_token as $key => $value) {
                $result = $user->updateMeta($key, $value);
                }
                return $this->loginResponse('Successfully Login', $user, true);
            }else{ // signup user
                if(isset($request->email)){
                    $emailCheck =  User::where('email',$request->email)->first();
                    if(!empty($emailCheck)){
                        return $this->responseWithError('Email exists', 402);
                    }
                }
                $newUser = new User;
                $newUser->social_id  = $request->social_id;
                $newUser->account_type = -1;
                $newUser->email  = $request->email?$request->email:null;
                $newUser->login_type = $request->login_type;   
                $newUser->save();
                Auth::login($newUser);
                $fcm_token = ['fcm_token' => request()->input('fcm_token')];
                foreach ($fcm_token as $key => $value) {
                $result = $newUser->updateMeta($key, $value);
                }
                return $this->loginResponse('Successfully Login', $newUser, true);
            }
            //return $this->responseWithError('social_id is not exist', 402);
        }
            
    }

    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validate = Validator::make(request()->all(), [
            'first_name'   => 'required|string',
            'last_name'    => 'required|string',
            'email'        => 'required|email|unique:users',
            'phone'        => 'required|unique:users',
            'account_type' => 'required'
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }
     //   try {
            if(!isset($request->login_type ) || $request->login_type == '0'){
                $validate = Validator::make(request()->all(), [
                    'password'   => 'required'
                ]);
            }else{
                $validate = Validator::make(request()->all(), [
                    'social_id'   => 'required'
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

            $user = User::create([
                'first_name'   => $request->input('first_name'),
                'last_name'    => $request->input('last_name'),
                'email'        => $request->input('email'),
                'phone'        => $request->input('phone'),
                'account_type' => $request->input('account_type'),
                'password'     => isset($request->password)?Hash::make($request->password):null,
                'login_type'   => !isset($request->login_type)?'0':$request->login_type,
                'social_id'    => isset($request->social_id)?$request->social_id:null
            ]);
            Auth::login($user);
            $fcm_token          = request()->input('fcm_token');
            $registerUserDetail = ['fcm_token' => $fcm_token ?? "", 'featured' => 0];
            foreach ($registerUserDetail as $key => $value) {
                $result = $user->updateMeta($key, $value);
            }

            try {
                PushSetting::updateOrCreate(
                    ['user_id' => $user->id],
                    ['status' => 0]
                );
            } catch (\Exception $e) {
                return $this->responseWithError($e->getMessage(), 402);
            }
            $users = User::find($user->id);
            return $this->loginResponse('Successfully Register', $users);
      //  } catch (\Exception $e) {
      //      return $this->responseWithError($e->getMessage(), 402);
      //  }

        return response()->json([
            'message' => 'User successfully registered',
            'user'    => $user,
        ], 201);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $user = User::find(Auth::user()->id);
        if (empty($user)) {
            return $this->responseWithError(self::USERNFOUND, 402);
        }
        $fcm_token = ['fcm_token' => ""];
        foreach ($fcm_token as $key => $value) {
            $result = $user->updateMeta($key, $value);
        }

        auth()->logout();
        return $this->respondWithMessage('Successfully logged out', true, 200);
    }

    public function setAccountType(Request $request){
        $validate = Validator::make(request()->all(), [
            'user_id'   => 'required',
            'account_type'    => 'required'
        ]);

        $validateMessage = '';
        if ($validate->fails()) {
            $errors = $validate->errors();
            foreach ($errors->all() as $message) {
                $validateMessage .= $message;
            }
            return $this->responseWithError($validateMessage, 402);
        }

        $user = User::find($request->user_id);
        $user->account_type  = $request->account_type;
        $user->save();
        return $this->loginResponse('Successfully Updated', $user);
    }
}
