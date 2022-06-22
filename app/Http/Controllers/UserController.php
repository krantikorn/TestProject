<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('users.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }



    //get password
    public function getPassword($id)
    {
        $decryptedID = Crypt::decryptString($id);
        $users       = User::find($decryptedID);
        if (!$users) {
            return view('email.not-found');
            //return $this->responseWithError(self::USERNFOUND, 402);
        }

        $forgetPassword = $this->getMetaValue($users, 'forgetPassword');
        $reset_id       = $this->getMetaValue($users, 'reset_id');
        if (($reset_id <=> $id) !== 0) {
            return view('errors.404')->with('message', 'Link has been expired');
        }
        if ($forgetPassword != 0) {
            return view('errors.404')->with('message', 'Link has been expired');
        }

        return view('email.password-reset', compact('id'));
    }

    //update password
    public function updatePassword()
    {
        $this->validate(request(), [
            'password' => 'min:6|required|confirmed',
        ]);

        $decryptedID = Crypt::decryptString(request()->input('badge'));
        $users       = User::find($decryptedID);

        $forgetPassword = $this->getMetaValue($users, 'forgetPassword');
        if ($forgetPassword != 0) {
            return view('errors.404')->with('message', 'Link has been expired');
        }

        $password = request()->input('password');
        $set      = ['password' => Hash::make($password)];
        $user     = User::where('id', $decryptedID)->update($set);

        if (!$user) {
            return view('errors.failed');
        }

        $registerUserMeta = ['forgetPassword' => 1];
        foreach ($registerUserMeta as $key => $value) {
            $result = $users->updateMeta($key, $value);
        }

        return view('email.thank-you');
    }
}
