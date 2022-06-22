@component('mail::message')
# Hello {{ $name }}

Please click the below link to reset password for your World Studio account

@component('mail::button', ['url' => url('reset/password/'.$id) ])
Reset Password
@endcomponent

If you didn’t ask to reset your password, you can ignore this email.

Thanks,<br>
Team {{ config('app.name') }}<br>
@endcomponent
