<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ (isset($page_title) ? __($page_title) : __("Public")) }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    @include('partials.header-asset')

    @stack('css')

    
</head>
<body class="{{ selectedLangDir() ?? "ltr"}}"> 
    <main> 
        @include('frontend.partials.preloader') 
        @include('frontend.partials.body-overlay')    
        @yield("content") 
        @include('partials.footer-asset') 
    </main> 
    @stack('script')
</body>
</html>