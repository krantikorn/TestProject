<!DOCTYPE html>
<html>
    <head>
        <title>
            World Studio
        </title>
        <link rel="icon" href="{{ URL::asset('/images/logo.png') }}" type="image/x-icon"/>
        <link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/mail.css') }}"/>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js" type="text/javascript">
        </script>
    </head>
    <body>
     <div class="logo">
         <img alt="logo" src="{{ URL::asset('/images/logo.png') }}">
     </div>
        <form action="{{ url('update/password') }}" method="post">
            @csrf
            <input name="badge" type="hidden" value="{{ $id }}"/>
            <p>
                <label for="password">
                    Password
                </label>
                <input id="password" name="password" type="password" required />
                <span style="display:none;" class="passwordSpan">
                    Password must be 6 characters long
                </span>
            </p>
            <p class="{{ $errors->has('password') ? ' has-error ' : '' }}">
                <label for="confirm_password">
                    Confirm Password
                </label>
                <input id="confirm_password" name="password_confirmation" type="password" required>
                @if ($errors->has('password'))
                    <span class="confirmPasswordSpan">
                        <strong>{{ $errors->first('password') }}</strong>
                    </span>
                @endif
            </p>
            <p>
                <input id="submit" type="submit" value="SUBMIT">
                </input>
            </p>
        </form>
        <script type="text/javascript" src="{{ URL::asset('/js/custom.js') }}"></script>
    </body>
</html>
