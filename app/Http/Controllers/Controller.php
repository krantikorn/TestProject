<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Traits\Meta;
use DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    use Meta;

    const INCORRECT      = 'The Old and New password cannot be the same';
    const PERCENTAGE     = '100';
    const SUCCESSUPDATED = 'Successfully updated';
    const ACCESSDENIED   = "You don't have access to create a course";
    const ERRORSOCIAL    = 'Email does not exists';
    const SUCCESSSAVED   = 'Successfully saved';
    const SUCCESSERROR   = 'Error while saving data';
    const SMAIL          = 'Instructions to reset the password is sent on your registered email address.';
    const ERMAIL         = 'Email not sent';
    const UPDATEERROR    = 'Error while update';
    const OLDPASSWORD    = 'Old Password is incorrect';
    const EMAILISSUE     = 'Email does not exists';
    const INPASS         = 'Incorrect Password';
    const USERFAILED     = 'User Registration Failed!';
    const USERNFOUND     = 'User not found!';
    const NOTIFYSTATUS   = 'Notification not found';
    const TYPE           = 'Type Not Found';
    const INTYPE         = 'Invalid Type or Social id is empty';
    const INACCTYPE      = 'Invalid Account Type';
    const BANNER         = 'Banner does not exists';
    const INLINK         = 'Invalid Password reset link';

    protected function checkConnection()
    {
        // Test database connection
        try {
            DB::connection()->getPdo();
        } catch (\Exception $e) {
            return $this->responseWithError($e->getMessage(), 402);
        }
    }

    //global function for error
    protected function responseWithError($message = '', $status = '')
    {
        $data = ['data' => (object) [], 'success' => false, 'message' => $message, 'status' => $status];
        return response()->json($data, 200);
    }

    //for pagination
    protected function responseWithDateWithPagination($message = '', $status = '', $raw = '')
    {
        if (!empty($raw)) {
            $data = array_values(array_filter($raw->items()));
        }

        return response()->json([
            'data'        => $data ?? [],
            'currentPage' => $raw->currentPage() ?? 0,
            'lastPage'    => $raw->lastPage() ?? 0,
            'total_items' => $raw->total() ?? 0,
            'success'     => true,
            'status'      => 200,
            'message'     => $message,
        ], $status);
    }

    //global for data and empty response
    protected function responseWithDataOREmpty($message = '', $status = '', $raw = '')
    {
        $data = ['data' => $raw ?? (object) [], 'success' => true, 'message' => $message, 'status' => $status];
        return response()->json($data, 200);
    }

    //response without token
    protected function responseWithoutToken($message, $users, $status)
    {
        $data = $this->evaluateRespondDataWithOutToken($users);

        return response()->json([
            'data'    => $data,
            'success' => true,
            'status'  => $status,
            'message' => $message ?? 'Success',
        ], $status);
    }

    //response with token
    protected function respondWithToken($message, $token, $users, $status, $profile = '')
    {
        $data = $this->evaluateRespondDataWithToken($users, $token, $profile);

        return response()->json([
            'data'    => $data,
            'success' => true,
            'status'  => $status,
            'message' => $message ?? 'Successfully Login',
        ], $status);
    }

    protected function respondWithNotificationRead($notifications = '', $status = '')
    {
        if ($notifications) {
            $data = array(
                'id'   => $notifications->id,
                'name' => $notifications->title,
                'read' => $notifications->status,
            );
        }

        return response()->json([
            'data'    => $data ?? [],
            'success' => true,
            'status'  => 200,
            'message' => 'We have marked the notification as read.',
        ], $status);
    }

    protected function respondWithNotifyData($notifications = '', $status = '')
    {
        if ($notifications) {
            $host = request()->getSchemeAndHttpHost();
            foreach ($notifications as $key => $value) {
                $image = '';
                $name  = '';
                if (!empty($value->from_id)) {
                    $user = User::find($value->from_id);
                    if (!empty($user)) {
                        $image = $this->getMetaValue($user, 'image');
                        $name  = $user->first_name . ' ' . $user->last_name;
                    }
                }

                $data[] = array(
                    'id'      => $value->id,
                    'title'   => $value->title,
                    'name'    => $name,
                    'image'   => !empty($image) ? $host . '/' . $image : 'null',
                    'message' => $value->message,
                    'read'    => $value->status,
                    'time'    => $value->updated_at,
                );
            }
        }

        $message = 'All notifications';
        if ($notifications->total() == 0) {
            $message = 'There is no notification at the moment';
        }

        return response()->json([
            'data'        => $data ?? [],
            'success'     => true,
            'status'      => 200,
            'message'     => $message,
            //'notification_count' => (string) count($notifications),
            'currentPage' => $notifications->currentPage() ?? 0,
            'lastPage'    => $notifications->lastPage() ?? 0,
            'total_items' => $notifications->total() ?? 0,
        ], $status);
    }

    protected function respondWithMessage($message, $success, $status)
    {
        return response()->json([
            'data'    => (object) [],
            'success' => $success,
            'status'  => $status,
            'message' => $message,
        ], $status);
    }

    protected function respondWithVerifiedData($users, $status)
    {
        $data = $this->evaluateRespondVerifiedData($users);

        return response()->json([
            'data'    => $data,
            'success' => true,
            'status'  => $status,
            'message' => 'Successfully Updated',
        ], $status);
    }

    public function respondAddressWithData($users, $status, $extradata = '', $ChMessage = '')
    {
        //$data['id'] = Auth::user()->ID ?? $users->id;
        foreach ($extradata as $key => $value) {
            $data[$key] = $value;
        }

        return response()->json([
            'data'    => $data,
            'success' => true,
            'status'  => $status,
            'message' => $ChMessage,
        ], $status);
    }

    protected function respondWithData($users, $status, $extradata = '')
    {
        $data          = $this->evaluateRespondData($users);
        $host          = request()->getSchemeAndHttpHost();
        $data['image'] = $host . '/' . $this->getMetaValue($users, 'image');

        if ($extradata) {
            $data['user_id'] = Auth::user()->ID ?? $users->id;
            foreach ($extradata as $key => $value) {
                $data[$key] = $value;
            }
        }

        return response()->json([
            'data'    => $data,
            'success' => true,
            'status'  => $status,
            'message' => 'Successfully Updated',
        ], $status);
    }

    protected function responseWithErrorPhone($message = '', $status = '', $register = 1)
    {
        $data = ['success' => false, 'message' => $message, 'status' => $status, 'notRegistered' => $register, 'data' => (object) []];
        return response()->json($data, 200);
    }

    /*protected function globalResponse($id, $success, $status, $message)
    {
        $orders = UserOrder::find($id);
        $data   = $this->evaluateGlobalRespondData($orders);

        return response()->json([
            'data'    => $data,
            'success' => $success,
            'status'  => $status,
            'message' => $message,
        ], $status);
    }*/

    protected function loginResponse($message, $users, $profile = '')
    {
        try {
            $token = Auth::login($users); //get Token
        } catch (\Exception $e) {
            return $this->responseWithError(self::USERFAILED, 402);
        }
        return $this->respondWithToken($message, $token, $users, 200, $profile);
    }
}
