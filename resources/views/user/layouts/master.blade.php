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
        <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start Dashboard
        ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
        <div class="page-wrapper">
          @include('user.partials.side-nav')
          <div class="main-wrapper">
              <div class="main-body-wrapper"> 
                  @include('user.partials.top-nav') 
                  @yield("content")
                </div>
              </div>
          </div>
          <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
              End Dashboard
          ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
        
        @include('partials.footer-asset')
        @include('user.partials.push-notification')
    </main>

    <script src="{{ asset('public/frontend/js/apexcharts.js') }}"></script>
    @stack('script')

    <script>
        // user account type change ajax 
        $('.user_type').on('click',function(){ 
            $.ajax({
            type:'get',
                url:"{{ route('user.profile.type.update') }}", 
                success:function(data){   
                    $.notify(
                            {
                                title: "",
                                message: data.success, 
                            },
                            {
                                type: "success",
                                allow_dismiss: true,
                                delay: 5000,
                                placement: {
                                from: "top",
                                align: "right"
                                },
                            }
                        );
                }
            }); 
        });
    </script>
</body>
</html>