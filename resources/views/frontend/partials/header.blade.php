@php
    $current_url = URL::current();
    $pages = App\Models\Admin\SetupPage::where(['type' => 'setup-page', 'status' => true])->orWhere('slug',"home")->get();
@endphp
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        Start Header
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->
    <header class="header-section @if ($current_url != setRoute('index')) two @endif">
        <div class="header">
            <div class="header-bottom-area">
                <div class="container">
                    <div class="header-menu-content">
                        <nav class="navbar navbar-expand-lg p-0">
                            <a class="site-logo site-title" href="{{ setRoute('index') }}">
                                <img src="{{ get_logo($basic_settings) }}" data-white_img="{{ get_logo($basic_settings,'white') }}"
                                data-dark_img="{{ get_logo($basic_settings,'dark') }}" alt="logo">
                            </a>
                            <button class="navbar-toggler ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent"
                                aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="fas fa-bars"></span>
                            </button>
                            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                                <ul class="navbar-nav main-menu ms-auto">
                                    @foreach ($pages as $item) 
                                    <li><a href="{{ url($item->url) }}" class="nav-link @if ($current_url == url($item->url)) active @endif">{{ __($item->title) }}</a></li>
                                    @endforeach
                                </ul>
                                <div class="header-language">
                                    @php
                                        $session_lan = session('local')??get_default_language_code();
                                    @endphp
                                    <select name="lang_switch" class="form--control language-select nice-select" id="language-select">
                                        @foreach($__languages as $item)
                                            <option value="{{$item->code}}" @if($session_lan == $item->code) selected  @endif>{{ __($item->name) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="header-action"> 
                                    @if (Auth::check())
                                    <a href="{{ setRoute('user.dashboard') }}" class="btn--base active"><i class="las la-user-tie"></i> {{ __('Dashboard') }}</a>
                                    @else
                                    <a href="{{ setRoute('user.login') }}" class="btn--base active"><i class="las la-user-tie"></i> {{ __('Login/Register') }}</a>
                                    @endif
                                </div>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!--~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
        End Header
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~-->