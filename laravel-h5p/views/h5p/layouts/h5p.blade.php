<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- CSRF Token -->
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <script>
            window.Laravel = <?php echo json_encode([ 'csrfToken' => csrf_token()]); ?>
        </script>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css?family=Lato:300,300i,400,400i,700,700i,900,900i" rel="stylesheet">
        <link rel="stylesheet" href="{{ url('css/app.css') }}"/>

        @stack('h5p-header-script')

        <!-- Styles -->
        <style>
            .full-height {
                height: 100vh;
            }

            .flex-center {
                align-items: center;
                display: flex;
                justify-content: center;
            }

            .position-ref {
                position: relative;
            }

            .top-right {
                position: absolute;
                right: 10px;
                top: 18px;
            }

            .content {
                text-align: center;
            }

            .title {
                font-size: 84px;
            }

            .links > a {
                color: #636b6f;
                padding: 0 25px;
                font-size: 12px;
                font-weight: 600;
                letter-spacing: .1rem;
                text-decoration: none;
                text-transform: uppercase;
            }

            .m-b-md {
                margin-bottom: 30px;
            }

            body {
                background: #ffffff;
            }
        </style>
    </head>

    <body>

        <div  id="app" 
              @if (Route::has('welcome'))
              class="flex-center position-ref full-height"
              @endif
              >

            @yield('h5p')

        </div>

        {{-- <script type="text/javascript" src="{{ url('js/app.js') }}"></script>         --}}
        @stack('h5p-footer-script')

    </body>
</html>
