        <!-- favicon -->
        <link rel="shortcut icon" href="{{ get_fav($basic_settings) }}" type="image/x-icon">
        <!-- fontawesome css link -->
        <link rel="stylesheet" href="{{ asset('public/frontend/css/fontawesome-all.css') }}">
        <!-- bootstrap css link -->
        <link rel="stylesheet" href="{{ asset('public/frontend/css/bootstrap.css') }}">
        <!-- swipper css link -->
        <link rel="stylesheet" href="{{ asset('public/frontend/css/swiper.css') }}">
        <!-- lightcase css links -->
        <link rel="stylesheet" href="{{ asset('public/frontend/css/lightcase.css') }}">
        <!-- odometer css link -->
        <link rel="stylesheet" href="{{ asset('public/frontend/css/odometer.css') }}">
        <!-- line-awesome-icon css -->
        <link rel="stylesheet" href="{{ asset('public/frontend/css/line-awesome.css') }}">
        <!-- animate.css -->
        <link rel="stylesheet" href="{{ asset('public/frontend/css/animate.css') }}">
        <!-- nice select css -->
        <link rel="stylesheet" href="{{ asset('public/frontend/css/nice-select.css') }}">
        <!-- Select 2 CSS -->
        <link rel="stylesheet" href="{{ asset('public/backend/css/select2.css') }}">
        <link rel="stylesheet" href="{{ asset('public/backend/library/popup/magnific-popup.css') }}">
        <!-- file holder css -->
        <link rel="stylesheet" href="https://cdn.appdevs.net/fileholder/v1.0/css/fileholder-style.css" type="text/css">
        <!-- main style css link -->
        <link rel="stylesheet" href="{{ asset('public/frontend/css/style.css') }}">

        @php
                $color = @$basic_settings->base_color ?? '#000000';
        
        @endphp
        
        <style>
                :root {
                --primary-color: {{$color}};
                }
        
        </style>
        


 