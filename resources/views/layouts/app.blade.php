<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'World Studio') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Styles -->
        <link rel="stylesheet" href="{{ mix('css/app.css') }}">
        <link rel="icon" href="{{ asset('images/logo.png') }}" type="image/x-icon"/>

        @livewireStyles

        <!-- Scripts -->
        <script src="{{ mix('js/app.js') }}" defer></script>
    </head>
    <body class="font-sans antialiased">
        <x-jet-banner />

        <div class="min-h-screen bg-gray-100">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            @if (isset($header))
                <!--header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header-->
            @endif

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @stack('modals')

        @livewireScripts
    </body>

    <script type="text/javascript">
        window.addEventListener('categoryStored', () => {
            $('#add-category').modal('hide');
            $('#add-sub-category').modal('hide');
        });
        window.addEventListener('categoryUpdated', () => {
            $('#edit-category').modal('hide');
            $('#update-sub-category').modal('hide');
        })
        window.addEventListener('groupUpdated', () => {
            $('#group-edit').modal('hide');
            $('.modal-backdrop').remove();
        });
    </script> 
    <script src="{{ asset('js/jquery-3.5.1.min.js') }}">
    </script>
    <script src="{{ asset('js/popper.min.js') }}">
    </script>
    <script src="{{ asset('js/bootstrap.min.js') }}">
    </script>
    <script src="{{ asset('js/moment.min.js') }}" type="text/javascript">
    </script>
</html>
